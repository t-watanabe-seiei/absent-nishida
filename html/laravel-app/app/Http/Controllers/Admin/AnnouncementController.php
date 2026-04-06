<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementAttachment;
use App\Models\AnnouncementRead;
use App\Models\ParentModel;
use App\Models\Student;
use App\Services\AnnouncementNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AnnouncementController extends Controller
{
    private const MAX_ATTACHMENTS = 5;
    private const MAX_FILE_SIZE_MB = 10;

    /**
     * お知らせ一覧取得
     * スーパー管理者: 全件
     * 担任: 担当クラス宛の全件（作成者不問）
     */
    public function index(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $query = Announcement::with(['admin', 'attachments'])
            ->withCount('reads')
            ->orderBy('created_at', 'desc');

        if (!$admin->is_super_admin) {
            if (!$admin->class_id) {
                return response()->json(['data' => [], 'total' => 0]);
            }
            $query->whereJsonContains('target_class_ids', $admin->class_id);
        }

        $perPage = (int) $request->get('per_page', 20);
        $announcements = $query->paginate($perPage);

        // 各お知らせの送信先総数を付加
        $announcements->getCollection()->transform(function ($announcement) {
            $announcement->total_targets_count = $this->calcTotalTargets($announcement);
            return $announcement;
        });

        return response()->json($announcements);
    }

    /**
     * お知らせ詳細取得
     */
    public function show(int $id)
    {
        $admin = Auth::guard('admin')->user();
        $announcement = Announcement::with(['admin', 'attachments', 'reads.parent'])->findOrFail($id);

        if (!$admin->is_super_admin) {
            if (!in_array($admin->class_id, $announcement->target_class_ids ?? [])) {
                return response()->json(['error' => 'アクセス権限がありません'], 403);
            }
        }

        $announcement->total_targets_count = $this->calcTotalTargets($announcement);

        return response()->json($announcement);
    }

    /**
     * お知らせ作成
     */
    public function store(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'body'               => 'required|string',
            'expires_at'         => 'required|date|after:now',
            'target_class_ids'   => 'required|array|min:1',
            'target_class_ids.*' => 'string',
            'target_parent_ids'  => 'nullable|array',
            'target_parent_ids.*'=> 'integer',
            'notify_by_email'    => 'boolean',
            'files'              => 'nullable|array|max:' . self::MAX_ATTACHMENTS,
            'files.*'            => 'file|mimes:pdf|max:' . (self::MAX_FILE_SIZE_MB * 1024),
        ]);

        // 担任は自分のクラスのみ対象にできる
        if (!$admin->is_super_admin) {
            $allowedClasses = [$admin->class_id];
            $requestedClasses = $validated['target_class_ids'];
            foreach ($requestedClasses as $classId) {
                if (!in_array($classId, $allowedClasses)) {
                    return response()->json(['error' => '担当クラス以外を対象にする権限がありません'], 403);
                }
            }
        }

        try {
            DB::beginTransaction();

            $announcement = Announcement::create([
                'admin_id'         => $admin->id,
                'title'            => $validated['title'],
                'body'             => $validated['body'],
                'expires_at'       => $validated['expires_at'],
                'target_class_ids' => $validated['target_class_ids'],
                'target_parent_ids'=> $validated['target_parent_ids'] ?? null,
                'notify_by_email'  => $validated['notify_by_email'] ?? false,
            ]);

            // PDFファイル保存
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $this->saveAttachment($announcement->id, $file);
                }
            }

            DB::commit();

            // メール通知
            if ($announcement->notify_by_email) {
                app(AnnouncementNotificationService::class)->notifyParents($announcement);
            }

            $announcement->load(['admin', 'attachments']);
            return response()->json($announcement, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('お知らせ作成エラー', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'お知らせの作成に失敗しました'], 500);
        }
    }

    /**
     * お知らせ編集
     */
    public function update(Request $request, int $id)
    {
        $admin = Auth::guard('admin')->user();
        $announcement = Announcement::findOrFail($id);

        // 権限チェック: 作成者またはスーパー管理者のみ
        if (!$admin->is_super_admin && $announcement->admin_id !== $admin->id) {
            return response()->json(['error' => '編集権限がありません'], 403);
        }

        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'body'               => 'required|string',
            'expires_at'         => 'required|date',
            'target_class_ids'   => 'required|array|min:1',
            'target_class_ids.*' => 'string',
            'target_parent_ids'  => 'nullable|array',
            'target_parent_ids.*'=> 'integer',
            'notify_by_email'    => 'boolean',
        ]);

        // 担任は自分のクラスのみ対象にできる
        if (!$admin->is_super_admin) {
            foreach ($validated['target_class_ids'] as $classId) {
                if ($classId !== $admin->class_id) {
                    return response()->json(['error' => '担当クラス以外を対象にする権限がありません'], 403);
                }
            }
        }

        $announcement->update([
            'title'             => $validated['title'],
            'body'              => $validated['body'],
            'expires_at'        => $validated['expires_at'],
            'target_class_ids'  => $validated['target_class_ids'],
            'target_parent_ids' => $validated['target_parent_ids'] ?? null,
            'notify_by_email'   => $validated['notify_by_email'] ?? false,
        ]);

        $announcement->load(['admin', 'attachments']);
        return response()->json($announcement);
    }

    /**
     * お知らせ削除
     */
    public function destroy(int $id)
    {
        $admin = Auth::guard('admin')->user();
        $announcement = Announcement::findOrFail($id);

        // 権限チェック: 作成者またはスーパー管理者のみ
        if (!$admin->is_super_admin && $announcement->admin_id !== $admin->id) {
            return response()->json(['error' => '削除権限がありません'], 403);
        }

        // storage のファイルを削除
        Storage::deleteDirectory("announcements/{$id}");
        $announcement->delete();

        return response()->json(['message' => 'お知らせを削除しました']);
    }

    /**
     * 既読状況一覧
     */
    public function readStatus(int $id)
    {
        $admin = Auth::guard('admin')->user();
        $announcement = Announcement::with(['reads.parent.student.classModel'])->findOrFail($id);

        if (!$admin->is_super_admin) {
            if (!in_array($admin->class_id, $announcement->target_class_ids ?? [])) {
                return response()->json(['error' => 'アクセス権限がありません'], 403);
            }
        }

        // 既読者
        $readParentIds = $announcement->reads->pluck('parent_id')->toArray();

        // 送信対象の全保護者を取得
        $targetParents = $this->resolveTargetParents($announcement);

        $readList = $targetParents->filter(fn($p) => in_array($p->id, $readParentIds))
            ->map(function ($p) use ($announcement) {
                $readRecord = $announcement->reads->firstWhere('parent_id', $p->id);
                return [
                    'parent_id'   => $p->id,
                    'parent_name' => $p->parent_name,
                    'seito_name'  => $p->student->seito_name ?? null,
                    'class_name'  => $p->student->classModel->class_name ?? null,
                    'read_at'     => $readRecord?->read_at,
                ];
            })->values();

        $unreadList = $targetParents->filter(fn($p) => !in_array($p->id, $readParentIds))
            ->map(fn($p) => [
                'parent_id'   => $p->id,
                'parent_name' => $p->parent_name,
                'seito_name'  => $p->student->seito_name ?? null,
                'class_name'  => $p->student->classModel->class_name ?? null,
                'read_at'     => null,
            ])->values();

        return response()->json([
            'announcement_id' => $id,
            'total'           => $targetParents->count(),
            'read_count'      => $readList->count(),
            'read_list'       => $readList,
            'unread_list'     => $unreadList,
        ]);
    }

    /**
     * 添付ファイル追加
     */
    public function addAttachment(Request $request, int $id)
    {
        $admin = Auth::guard('admin')->user();
        $announcement = Announcement::findOrFail($id);

        // 権限チェック
        if (!$admin->is_super_admin && $announcement->admin_id !== $admin->id) {
            return response()->json(['error' => '権限がありません'], 403);
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:' . (self::MAX_FILE_SIZE_MB * 1024)],
        ]);

        $currentCount = AnnouncementAttachment::where('announcement_id', $id)->count();
        if ($currentCount >= self::MAX_ATTACHMENTS) {
            return response()->json(['error' => "添付ファイルは最大 " . self::MAX_ATTACHMENTS . " 件までです"], 422);
        }

        $attachment = $this->saveAttachment($id, $request->file('file'));

        return response()->json($attachment, 201);
    }

    /**
     * 添付ファイル削除
     */
    public function removeAttachment(int $id, int $attachId)
    {
        $admin = Auth::guard('admin')->user();
        $announcement = Announcement::findOrFail($id);
        $attachment = AnnouncementAttachment::where('announcement_id', $id)->findOrFail($attachId);

        // 権限チェック
        if (!$admin->is_super_admin && $announcement->admin_id !== $admin->id) {
            return response()->json(['error' => '権限がありません'], 403);
        }

        Storage::delete($attachment->stored_path);
        $attachment->delete();

        return response()->json(['message' => '添付ファイルを削除しました']);
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * アップロードされたファイルを保存し AnnouncementAttachment レコードを作成
     */
    private function saveAttachment(int $announcementId, $file): AnnouncementAttachment
    {
        $filename   = Str::uuid() . '.pdf';
        $directory  = "announcements/{$announcementId}";
        $storedPath = $file->storeAs($directory, $filename);

        return AnnouncementAttachment::create([
            'announcement_id' => $announcementId,
            'original_name'   => $file->getClientOriginalName(),
            'stored_path'     => $storedPath,
        ]);
    }

    /**
     * お知らせの送信先保護者を解決する
     */
    private function resolveTargetParents(Announcement $announcement)
    {
        $targetParentIds = $announcement->target_parent_ids;

        if (!is_null($targetParentIds) && count($targetParentIds) > 0) {
            return ParentModel::with(['student.classModel'])
                ->whereIn('id', $targetParentIds)
                ->get();
        }

        $targetClassIds = $announcement->target_class_ids ?? [];
        if (empty($targetClassIds)) {
            return collect();
        }

        $seitoIds = Student::whereIn('class_id', $targetClassIds)
            ->pluck('seito_id')
            ->toArray();

        if (empty($seitoIds)) {
            return collect();
        }

        return ParentModel::with(['student.classModel'])
            ->whereIn('seito_id', $seitoIds)
            ->get();
    }

    /**
     * 送信先総数を計算する
     */
    private function calcTotalTargets(Announcement $announcement): int
    {
        return $this->resolveTargetParents($announcement)->count();
    }
}
