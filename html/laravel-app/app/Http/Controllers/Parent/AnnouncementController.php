<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementAttachment;
use App\Models\AnnouncementRead;
use App\Models\Student;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AnnouncementController extends Controller
{
    /**
     * ダッシュボード用お知らせ一覧取得
     * 機能ON かつ 有効期限内 かつ 対象保護者のみ返す
     * 未読のものを自動既読登録する
     */
    public function index()
    {
        // 機能 OFF の場合は空配列を返す
        if (SystemSetting::getValue('announcement_enabled', '0') !== '1') {
            return response()->json([]);
        }

        $parent = Auth::guard('parent')->user();

        // 保護者の生徒クラスIDを取得
        $student = Student::where('seito_id', $parent->seito_id)->first();
        $classId = $student?->class_id;

        // 対象お知らせを取得
        $announcements = Announcement::with('attachments')
            ->active()
            ->where(function ($query) use ($classId, $parent) {
                // クラス対象 + 個別保護者未指定（全員）
                $query->where(function ($q) use ($classId) {
                    if ($classId) {
                        $q->whereJsonContains('target_class_ids', $classId)
                          ->whereNull('target_parent_ids');
                    } else {
                        $q->whereRaw('1=0'); // クラス不明の場合はクラス対象外
                    }
                })
                // 個別保護者指定
                ->orWhere(function ($q) use ($parent) {
                    $q->whereJsonContains('target_parent_ids', $parent->id);
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // 未読のものを一括で既読登録
        $now   = Carbon::now();
        $readIds = AnnouncementRead::where('parent_id', $parent->id)
            ->whereIn('announcement_id', $announcements->pluck('id'))
            ->pluck('announcement_id')
            ->toArray();

        $toInsert = $announcements->pluck('id')->diff($readIds)->values();
        foreach ($toInsert as $announcementId) {
            AnnouncementRead::firstOrCreate(
                ['announcement_id' => $announcementId, 'parent_id' => $parent->id],
                ['read_at' => $now]
            );
        }

        return response()->json($announcements);
    }

    /**
     * 既読登録（明示的）
     */
    public function read(int $id)
    {
        $parent = Auth::guard('parent')->user();
        $announcement = Announcement::findOrFail($id);

        AnnouncementRead::firstOrCreate(
            ['announcement_id' => $announcement->id, 'parent_id' => $parent->id],
            ['read_at' => Carbon::now()]
        );

        return response()->json(['message' => '既読登録しました']);
    }

    /**
     * 添付 PDF ダウンロード
     */
    public function downloadAttachment(int $id, int $attachId)
    {
        // 機能 OFF の場合は拒否
        if (SystemSetting::getValue('announcement_enabled', '0') !== '1') {
            return response()->json(['error' => 'お知らせ機能は現在利用できません'], 403);
        }

        $parent = Auth::guard('parent')->user();
        $announcement = Announcement::findOrFail($id);

        // 保護者が対象かを確認
        if (!$this->isTargetParent($announcement, $parent)) {
            return response()->json(['error' => 'このお知らせへのアクセス権限がありません'], 403);
        }

        $attachment = AnnouncementAttachment::where('announcement_id', $id)->findOrFail($attachId);

        if (!Storage::exists($attachment->stored_path)) {
            return response()->json(['error' => 'ファイルが見つかりません'], 404);
        }

        return Storage::download($attachment->stored_path, $attachment->original_name);
    }

    /**
     * 保護者がお知らせの対象かどうかを判定
     */
    private function isTargetParent(Announcement $announcement, $parent): bool
    {
        // 個別指定がある場合
        $targetParentIds = $announcement->target_parent_ids;
        if (!is_null($targetParentIds)) {
            return in_array($parent->id, $targetParentIds);
        }

        // クラス全員の場合
        $student = Student::where('seito_id', $parent->seito_id)->first();
        if (!$student) {
            return false;
        }

        return in_array($student->class_id, $announcement->target_class_ids ?? []);
    }
}
