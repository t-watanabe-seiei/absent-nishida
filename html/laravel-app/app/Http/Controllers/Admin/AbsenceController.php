<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Absence;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AbsenceController extends Controller
{
    /**
     * 本日の欠席一覧取得
     */
    public function today()
    {
        $absences = Absence::with(['student.classModel'])
            ->where('is_deleted', false) // 削除されていないもののみ
            ->whereDate('absence_date', Carbon::today())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($absences);
    }

    /**
     * 欠席一覧取得（日付指定可能）
     */
    public function index(Request $request)
    {
        $query = $this->buildAbsenceQuery($request);

        $absences = $query->orderBy('absence_date', 'desc')
                         ->orderBy('created_at', 'desc')
                         ->paginate($request->get('per_page', 20));

        return response()->json($absences);
    }

    /**
     * 欠席一覧 CSV エクスポート
     * フィルター条件に合う全件を UTF-8 BOM 付き CSV でダウンロード
     */
    public function export(Request $request)
    {
        $query = $this->buildAbsenceQuery($request);

        $absences = $query->orderBy('absence_date', 'desc')
                          ->orderBy('created_at', 'desc')
                          ->get();

        $filename = 'absences_' . Carbon::today()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($absences) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM（Excelで日本語が文字化けしないよう付与）
            fwrite($handle, "\xEF\xBB\xBF");

            // ヘッダー行
            fputcsv($handle, ['日付', '学年', 'クラス', '出席番号', '氏名', '区分', '理由', '予定時刻']);

            foreach ($absences as $item) {
                $className = $item->student->classModel->class_name ?? '';
                // 学年: クラス名の先頭1文字 + "年"
                $grade = preg_match('/^(\d)/', $className, $m) ? $m[1] . '年' : '-';

                $row = [
                    $item->absence_date
                        ? Carbon::parse($item->absence_date)->format('Y/m/d')
                        : '-',
                    $grade,
                    $className ?: '-',
                    $item->student->seito_number ?? '-',
                    $item->student->seito_name ?? '-',
                    $item->division ?? '',
                    self::sanitizeCsvCell($item->reason ?? ''),
                    $item->scheduled_time ?? '',
                ];

                fputcsv($handle, $row);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * 欠席連絡登録（管理者作成）
     */
    public function store(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $validated = $request->validate([
            'seito_id'       => 'required|string|exists:students,seito_id',
            'division'       => 'required|string|in:欠席,遅刻,早退',
            'reason'         => 'required|string|max:1000',
            'scheduled_time' => 'nullable|string|max:50',
            'absence_date'   => 'required|date',
        ]);

        // 担任は自分のクラスの生徒のみ登録可能
        if (!$admin->is_super_admin) {
            $exists = Student::where('seito_id', $validated['seito_id'])
                ->where('class_id', $admin->class_id)
                ->exists();
            if (!$exists) {
                return response()->json([
                    'message' => '担当クラス以外の生徒の欠席連絡は登録できません。',
                ], 403);
            }
        }

        $absence = Absence::create(array_merge($validated, ['is_admin_created' => true]));

        return response()->json([
            'message' => '欠席連絡を登録しました。',
            'absence' => $absence->load('student.classModel'),
        ], 201);
    }

    /**
     * 欠席連絡更新（管理者作成のもののみ）
     */
    public function update(Request $request, $id)
    {
        $admin = Auth::guard('admin')->user();

        $absence = Absence::where('is_admin_created', true)
            ->where('is_deleted', false)
            ->findOrFail($id);

        // 担任は自分のクラスの生徒のもののみ更新可能
        if (!$admin->is_super_admin) {
            $exists = Student::where('seito_id', $absence->seito_id)
                ->where('class_id', $admin->class_id)
                ->exists();
            if (!$exists) {
                return response()->json(['message' => '変更権限がありません。'], 403);
            }
        }

        $validated = $request->validate([
            'division'       => 'sometimes|string|in:欠席,遅刻,早退',
            'reason'         => 'sometimes|string|max:1000',
            'scheduled_time' => 'nullable|string|max:50',
            'absence_date'   => 'sometimes|date',
        ]);

        $absence->update($validated);

        return response()->json([
            'message' => '欠席連絡を更新しました。',
            'absence' => $absence->load('student.classModel'),
        ]);
    }

    /**
     * 欠席連絡削除（管理者作成のもののみ・論理削除）
     */
    public function destroy($id)
    {
        $admin = Auth::guard('admin')->user();

        $absence = Absence::where('is_admin_created', true)
            ->where('is_deleted', false)
            ->findOrFail($id);

        // 担任は自分のクラスの生徒のもののみ削除可能
        if (!$admin->is_super_admin) {
            $exists = Student::where('seito_id', $absence->seito_id)
                ->where('class_id', $admin->class_id)
                ->exists();
            if (!$exists) {
                return response()->json(['message' => '削除権限がありません。'], 403);
            }
        }

        $absence->update(['is_deleted' => true, 'deleted_at' => Carbon::now()]);

        return response()->json(['message' => '欠席連絡を削除しました。']);
    }

    /**
     * CSVインジェクション対策: 先頭が = + - @ の場合はタブを付加
     */
    private static function sanitizeCsvCell(string $value): string
    {
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "\t" . $value;
        }
        return $value;
    }

    /**
     * 欠席クエリをフィルター条件に基づいて構築する共通メソッド
     */
    private function buildAbsenceQuery(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $query = Absence::with(['student.classModel'])
            ->where('is_deleted', false);

        // 担任の場合は、show_all_classesパラメータがない限り自分のクラスのみ表示
        $showAllClasses = $request->has('show_all_classes') && $request->show_all_classes === 'true';

        if ($admin && !$admin->is_super_admin && $admin->class_id && !$showAllClasses) {
            $query->whereHas('student', function ($q) use ($admin) {
                $q->where('class_id', $admin->class_id);
            });
        }

        // 日付範囲でフィルタ
        if ($request->has('date_from') && $request->date_from !== '') {
            $query->whereDate('absence_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to !== '') {
            $query->whereDate('absence_date', '<=', $request->date_to);
        }

        // 単一日付でフィルタ（後方互換性）
        if ($request->has('date') && $request->date !== '') {
            $query->whereDate('absence_date', $request->date);
        }

        // 区分でフィルタ
        if ($request->has('division') && $request->division !== '') {
            $query->where('division', $request->division);
        }

        // 学年でフィルタ
        if ($request->has('grade') && $request->grade !== '') {
            $grade = $request->grade;
            $query->whereHas('student.classModel', function ($q) use ($grade) {
                $q->where('class_name', 'LIKE', $grade . '%');
            });
        }

        // クラスIDでフィルタ
        if ($request->has('class_id')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('class_id', $request->class_id);
            });
        }

        // クラス名でフィルタ
        if ($request->has('class_name')) {
            $query->whereHas('student.classModel', function ($q) use ($request) {
                $q->where('class_name', $request->class_name);
            });
        }

        return $query;
    }

    /**
     * 欠席統計情報取得
     */
    public function stats(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        
        // show_all_classesパラメータで全クラス表示かどうかを判定
        $showAllClasses = $request->has('show_all_classes') && $request->show_all_classes === 'true';
        
        // 本日の欠席数
        $todayQuery = Absence::where('is_deleted', false)
                       ->whereDate('absence_date', Carbon::today())
                       ->where('division', '欠席');
        
        // 担任の場合は、show_all_classesがない限り自分のクラスのみ
        if ($admin && !$admin->is_super_admin && $admin->class_id && !$showAllClasses) {
            $todayQuery->whereHas('student', function ($q) use ($admin) {
                $q->where('class_id', $admin->class_id);
            });
        }
        $today = $todayQuery->count();

        // 今週の欠席数（月曜日〜日曜日）
        $weekQuery = Absence::where('is_deleted', false)
                ->whereBetween('absence_date', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ])
                ->where('division', '欠席');
        
        if ($admin && !$admin->is_super_admin && $admin->class_id && !$showAllClasses) {
            $weekQuery->whereHas('student', function ($q) use ($admin) {
                $q->where('class_id', $admin->class_id);
            });
        }
        $week = $weekQuery->count();

        // 今月の欠席数
        $monthQuery = Absence::where('is_deleted', false)
                       ->whereYear('absence_date', Carbon::now()->year)
                       ->whereMonth('absence_date', Carbon::now()->month)
                       ->where('division', '欠席');
        
        if ($admin && !$admin->is_super_admin && $admin->class_id && !$showAllClasses) {
            $monthQuery->whereHas('student', function ($q) use ($admin) {
                $q->where('class_id', $admin->class_id);
            });
        }
        $month = $monthQuery->count();

        // 総欠席数
        $totalQuery = Absence::where('is_deleted', false)
                       ->where('division', '欠席');
        
        if ($admin && !$admin->is_super_admin && $admin->class_id && !$showAllClasses) {
            $totalQuery->whereHas('student', function ($q) use ($admin) {
                $q->where('class_id', $admin->class_id);
            });
        }
        $total = $totalQuery->count();

        return response()->json([
            'today' => $today,
            'week' => $week,
            'month' => $month,
            'total' => $total,
        ]);
    }

    /**
     * 月別欠席統計取得
     */
    public function monthly(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $currentYear = Carbon::now()->year;
        $monthlyData = [];
        
        // show_all_classesパラメータで全クラス表示かどうかを判定
        $showAllClasses = $request->has('show_all_classes') && $request->show_all_classes === 'true';

        // 過去6ヶ月分のデータを取得（新しい月から古い月へ）
        for ($i = 0; $i <= 5; $i++) {
            $date = Carbon::now()->subMonths($i);
            $query = Absence::where('is_deleted', false)
                           ->whereYear('absence_date', $date->year)
                           ->whereMonth('absence_date', $date->month)
                           ->where('division', '欠席');
            
            // 担任の場合は、show_all_classesがない限り自分のクラスのみ
            if ($admin && !$admin->is_super_admin && $admin->class_id && !$showAllClasses) {
                $query->whereHas('student', function ($q) use ($admin) {
                    $q->where('class_id', $admin->class_id);
                });
            }
            
            $count = $query->count();

            $monthlyData[] = [
                'month' => $date->format('Y年n月'),
                'count' => $count
            ];
        }

        return response()->json($monthlyData);
    }

    /**
     * 欠席詳細取得
     */
    public function show($id)
    {
        $absence = Absence::where('is_deleted', false)
                         ->with(['student.classModel'])
                         ->findOrFail($id);
        return response()->json($absence);
    }
}
