<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CsvImportService;
use Illuminate\Http\Request;

class ImportController extends Controller
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

        // ファイルバリデーション
        if (!$this->csvImportService->validateCSVFile($file)) {
            return response()->json([
                'message' => 'CSVファイルが不正です',
            ], 422);
        }

        try {
            // CSVをパース
            $data = $this->csvImportService->parseCSV($file->getRealPath());

            if (empty($data)) {
                return response()->json([
                    'message' => 'CSVファイルにデータがありません',
                ], 422);
            }

            // インポート実行
            $result = $this->csvImportService->importStudents($data);

            return response()->json([
                'message' => "生徒データをインポートしました",
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'インポート中にエラーが発生しました: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * クラスデータインポート
     */
    public function importClasses(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');

        // ファイルバリデーション
        if (!$this->csvImportService->validateCSVFile($file)) {
            return response()->json([
                'message' => 'CSVファイルが不正です',
            ], 422);
        }

        try {
            // CSVをパース
            $data = $this->csvImportService->parseCSV($file->getRealPath());

            if (empty($data)) {
                return response()->json([
                    'message' => 'CSVファイルにデータがありません',
                ], 422);
            }

            // インポート実行
            $result = $this->csvImportService->importClasses($data);

            return response()->json([
                'message' => "クラスデータをインポートしました",
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'インポート中にエラーが発生しました: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 教員データ（クラス担任）インポート
     */
    public function importTeachers(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');

        // ファイルバリデーション
        if (!$this->csvImportService->validateCSVFile($file)) {
            return response()->json([
                'message' => 'CSVファイルが不正です',
            ], 422);
        }

        try {
            // CSVをパース
            $data = $this->csvImportService->parseCSV($file->getRealPath());

            if (empty($data)) {
                return response()->json([
                    'message' => 'CSVファイルにデータがありません',
                ], 422);
            }

            // インポート実行
            $result = $this->csvImportService->importTeachers($data);

            return response()->json([
                'message' => "教員データをインポートしました",
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'インポート中にエラーが発生しました: ' . $e->getMessage(),
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

        // ファイルバリデーション
        if (!$this->csvImportService->validateCSVFile($file)) {
            return response()->json([
                'message' => 'CSVファイルが不正です',
            ], 422);
        }

        try {
            // CSVをパース
            $data = $this->csvImportService->parseCSV($file->getRealPath());

            if (empty($data)) {
                return response()->json([
                    'message' => 'CSVファイルにデータがありません',
                ], 422);
            }

            // インポート実行
            $result = $this->csvImportService->importParents($data);

            return response()->json([
                'message' => "保護者データをインポートしました（{$result['success']}件成功）",
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'インポート中にエラーが発生しました: ' . $e->getMessage(),
            ], 500);
        }
    }
}
