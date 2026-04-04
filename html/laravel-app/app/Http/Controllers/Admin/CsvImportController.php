<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CsvImportService;
use Illuminate\Http\Request;

class CsvImportController extends Controller
{
    protected $csvImportService;

    public function __construct(CsvImportService $csvImportService)
    {
        $this->csvImportService = $csvImportService;
    }

    /**
     * 生徒データインポート
     */
    public function importStudents(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');

        if (!$this->csvImportService->validateCSVFile($file)) {
            return response()->json([
                'message' => 'CSVファイルが無効です',
            ], 422);
        }

        try {
            $data = $this->csvImportService->parseCSV($file->getRealPath());
            $result = $this->csvImportService->importStudents($data);

            return response()->json([
                'message' => "{$result['success']}件の生徒データをインポートしました",
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'インポートに失敗しました: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 保護者データインポート
     */
    public function importParents(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');

        if (!$this->csvImportService->validateCSVFile($file)) {
            return response()->json([
                'message' => 'CSVファイルが無効です',
            ], 422);
        }

        try {
            $data = $this->csvImportService->parseCSV($file->getRealPath());
            $result = $this->csvImportService->importParents($data);

            return response()->json([
                'message' => "{$result['success']}件の保護者データをインポートしました",
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'インポートに失敗しました: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 管理者データインポート
     */
    public function importAdmins(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');

        if (!$this->csvImportService->validateCSVFile($file)) {
            return response()->json([
                'message' => 'CSVファイルが無効です',
            ], 422);
        }

        try {
            $data = $this->csvImportService->parseCSV($file->getRealPath());
            $result = $this->csvImportService->importAdmins($data);

            return response()->json([
                'message' => "{$result['success']}件の管理者データをインポートしました",
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'インポートに失敗しました: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 生徒クラス一括更新（年度切り替え用）
     */
    public function importStudentClasses(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');

        if (!$this->csvImportService->validateCSVFile($file)) {
            return response()->json([
                'message' => 'CSVファイルが無効です',
            ], 422);
        }

        try {
            $data = $this->csvImportService->parseCSV($file->getRealPath());
            $result = $this->csvImportService->importStudentClasses($data);

            return response()->json([
                'message' => "{$result['success']}件の生徒クラスを更新しました（スキップ: {$result['skipped']}件）",
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'インポートに失敗しました: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * CSVテンプレートダウンロード
     */
    public function downloadTemplate(Request $request, string $type)
    {
        $templates = [
            'students' => [
                'filename' => 'students_template.csv',
                'headers' => ['seito_id', 'seito_name', 'seito_number', 'class_id', 'seito_initial_email'],
                'sample' => ['1001', '山田太郎', '1', '1TOKUSHIN', '1001@seiei.ac.jp'],
            ],
            'parents' => [
                'filename' => 'parents_template.csv',
                'headers' => ['seito_id', 'parent_name', 'parent_initial_email', 'parent_initial_password'],
                'sample' => ['1001', '山田一郎', 'yamada@example.com', 'password123'],
            ],
            'admins' => [
                'filename' => 'admins_template.csv',
                'headers'  => ['name', 'email', 'password', 'class_id', 'is_super_admin'],
                'samples'  => [
                    ['担任教師', 'teacher1tokushin@seiei.ac.jp', 'seiei2026', '1TOKUSHIN', 'false'],
                    ['スーパー管理者', 'admin@seiei.ac.jp', 'seiei2026', '', 'true'],
                ],
            ],
            'classes' => [
                'filename' => 'classes_template.csv',
                'headers'  => ['class_id', 'class_name', 'teacher_name', 'teacher_email', 'year_id'],
                'sample'   => ['1TOKUSHIN', '1特進', '田中先生', 'tanaka@seiei.ac.jp', '2026'],
            ],
        ];

        if (!isset($templates[$type])) {
            return response()->json(['message' => '不正なテンプレートタイプです'], 404);
        }

        $template = $templates[$type];

        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, $template['headers']);
        // samples（複数行）か sample（1行）かを吸収する
        $sampleRows = $template['samples'] ?? [$template['sample']];
        foreach ($sampleRows as $sampleRow) {
            fputcsv($csv, $sampleRow);
        }
        rewind($csv);
        
        $content = stream_get_contents($csv);
        fclose($csv);

        return response($content, 200)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename={$template['filename']}");
    }
}
