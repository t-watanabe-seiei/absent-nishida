<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\ClassModel;
use App\Models\ParentModel;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CsvImportService
{
    /**
     * CSVファイルをパースして配列に変換
     */
    public function parseCSV($filePath): array
    {
        $data = [];
        
        if (($handle = fopen($filePath, 'r')) !== false) {
            // ヘッダー行を取得
            $header = fgetcsv($handle);
            
            // データ行を取得
            while (($row = fgetcsv($handle)) !== false) {
                if (count($header) === count($row)) {
                    $data[] = array_combine($header, $row);
                }
            }
            
            fclose($handle);
        }
        
        return $data;
    }

    /**
     * クラスデータをインポート
     */
    public function importClasses(array $data): array
    {
        $errors = [];
        $success = 0;
        
        DB::beginTransaction();
        
        try {
            foreach ($data as $index => $row) {
                $validator = Validator::make($row, [
                    'class_id' => 'required|string',
                    'class_name' => 'required|string|max:255',
                    'teacher_name' => 'required|string|max:255',
                    'teacher_email' => 'required|email|max:255',
                    'year_id' => 'required|integer|min:2000|max:2100',
                ]);
                
                if ($validator->fails()) {
                    $errors[] = [
                        'row' => $index + 2, // ヘッダー行を考慮
                        'data' => $row,
                        'errors' => $validator->errors()->all(),
                    ];
                    continue;
                }
                
                // 既存チェック（更新または新規作成）
                ClassModel::updateOrCreate(
                    ['class_id' => $row['class_id']],
                    [
                        'class_name' => $row['class_name'],
                        'teacher_name' => $row['teacher_name'],
                        'teacher_email' => $row['teacher_email'],
                        'year_id' => $row['year_id'],
                    ]
                );
                
                $success++;
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        
        return [
            'success' => $success,
            'errors' => $errors,
            'total' => count($data),
        ];
    }

    /**
     * 生徒データをインポート
     */
    public function importStudents(array $data): array
    {
        $errors = [];
        $success = 0;
        
        DB::beginTransaction();
        
        try {
            foreach ($data as $index => $row) {
                $validator = Validator::make($row, [
                    'seito_id' => 'required|string',
                    'seito_name' => 'required|string|max:255',
                    'seito_number' => 'required|integer|min:1',
                    'class_id' => 'required|string',
                ]);
                
                if ($validator->fails()) {
                    $errors[] = [
                        'row' => $index + 2,
                        'data' => $row,
                        'errors' => $validator->errors()->all(),
                    ];
                    continue;
                }
                
                // クラスが存在するか確認
                $class = ClassModel::where('class_id', $row['class_id'])->first();
                
                if (!$class) {
                    $errors[] = [
                        'row' => $index + 2,
                        'data' => $row,
                        'errors' => ["クラスID '{$row['class_id']}' が見つかりません"],
                    ];
                    continue;
                }
                
                // 既存チェック（更新または新規作成）
                Student::updateOrCreate(
                    ['seito_id' => $row['seito_id']],
                    [
                        'seito_name' => $row['seito_name'],
                        'seito_number' => $row['seito_number'],
                        'class_id' => $row['class_id'], // 文字列のclass_idをそのまま使用
                        'seito_initial_email' => $row['seito_initial_email'] ?? null,
                    ]
                );
                
                $success++;
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        
        return [
            'success' => $success,
            'errors' => $errors,
            'total' => count($data),
        ];
    }

    /**
     * 教員データ（クラス担任）をインポート
     */
    public function importTeachers(array $data): array
    {
        $errors = [];
        $success = 0;
        
        DB::beginTransaction();
        
        try {
            foreach ($data as $index => $row) {
                $validator = Validator::make($row, [
                    'class_id' => 'required|string',
                    'teacher_name' => 'required|string|max:255',
                    'teacher_email' => 'required|email|max:255',
                ]);
                
                if ($validator->fails()) {
                    $errors[] = [
                        'row' => $index + 2,
                        'data' => $row,
                        'errors' => $validator->errors()->all(),
                    ];
                    continue;
                }
                
                // クラスを検索して更新
                $class = ClassModel::where('class_id', $row['class_id'])->first();
                
                if (!$class) {
                    $errors[] = [
                        'row' => $index + 2,
                        'data' => $row,
                        'errors' => ["クラスID '{$row['class_id']}' が見つかりません"],
                    ];
                    continue;
                }
                
                $class->update([
                    'teacher_name' => $row['teacher_name'],
                    'teacher_email' => $row['teacher_email'],
                ]);
                
                $success++;
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        
        return [
            'success' => $success,
            'errors' => $errors,
            'total' => count($data),
        ];
    }

    /**
     * CSVファイルのバリデーション
     */
    public function validateCSVFile($file): bool
    {
        // ファイルサイズチェック（2MB以下）
        if ($file->getSize() > 2 * 1024 * 1024) {
            return false;
        }
        
        // MIMEタイプチェック
        $mimeType = $file->getMimeType();
        $allowedMimes = ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'];
        
        if (!in_array($mimeType, $allowedMimes)) {
            return false;
        }
        
        // 拡張子チェック
        $extension = $file->getClientOriginalExtension();
        if ($extension !== 'csv') {
            return false;
        }
        
        return true;
    }

    /**
     * 保護者データをインポート
     */
    public function importParents(array $data): array
    {
        $errors = [];
        $success = 0;
        $credentials = [];
        
        DB::beginTransaction();
        
        try {
            foreach ($data as $index => $row) {
                $validator = Validator::make($row, [
                    'seito_id' => 'required|string',
                    'parent_name' => 'required|string|max:255',
                    'parent_initial_email' => 'required|email|max:255',
                    'parent_initial_password' => 'required|string',
                ]);
                
                if ($validator->fails()) {
                    $errors[] = [
                        'row' => $index + 2,
                        'data' => $row,
                        'errors' => $validator->errors()->all(),
                    ];
                    continue;
                }
                
                // 生徒が存在するか確認
                $student = Student::where('seito_id', $row['seito_id'])->first();
                
                if (!$student) {
                    $errors[] = [
                        'row' => $index + 2,
                        'data' => $row,
                        'errors' => ["生徒ID '{$row['seito_id']}' が見つかりません"],
                    ];
                    continue;
                }
                
                // parent_initial_emailの重複チェック
                $existingParent = ParentModel::where('parent_initial_email', $row['parent_initial_email'])
                    ->where('seito_id', '!=', $row['seito_id'])
                    ->first();
                
                if ($existingParent) {
                    $errors[] = [
                        'row' => $index + 2,
                        'data' => $row,
                        'errors' => ["初期メールアドレス '{$row['parent_initial_email']}' は既に使用されています"],
                    ];
                    continue;
                }
                
                // CSVの平文パスワードを保存（表示用）
                $initialPassword = $row['parent_initial_password'];
                
                // 既存チェック（seito_idで更新または新規作成）
                $parent = ParentModel::updateOrCreate(
                    ['seito_id' => $row['seito_id']],
                    [
                        'parent_name' => $row['parent_name'],
                        'parent_tel' => null,
                        'parent_relationship' => '保護者',
                        'parent_initial_email' => $row['parent_initial_email'],
                        'parent_initial_password' => $initialPassword, // Laravel自動ハッシュ化（castsで設定済み）
                        'parent_email' => null, // 初回ログイン時に登録
                        'parent_password' => null, // 将来の拡張用
                    ]
                );
                
                // 認証情報を記録（管理者への表示用）
                $credentials[] = [
                    'seito_id' => $row['seito_id'],
                    'seito_name' => $student->seito_name,
                    'parent_name' => $row['parent_name'],
                    'email' => $row['parent_initial_email'],
                    'password' => $initialPassword,
                ];
                
                $success++;
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        
        return [
            'success' => $success,
            'errors' => $errors,
            'total' => count($data),
            'credentials' => $credentials,
        ];
    }

    /**
     * 管理者データをインポート
     */
    public function importAdmins(array $data): array
    {
        $errors = [];
        $success = 0;
        
        DB::beginTransaction();
        
        try {
            foreach ($data as $index => $row) {
                $validator = Validator::make($row, [
                    'name' => 'required|string|max:255',
                    'email' => 'nullable|email|max:255',
                    'password' => 'required|string|min:6',
                ]);
                
                if ($validator->fails()) {
                    $errors[] = [
                        'row' => $index + 2,
                        'data' => $row,
                        'errors' => $validator->errors()->all(),
                    ];
                    continue;
                }
                
                // emailがある場合はそれをユニークキーとし、ない場合はnameをユニークキーとする
                $uniqueKey = !empty($row['email']) ? ['email' => $row['email']] : ['name' => $row['name']];
                
                // 既存チェック（更新または新規作成）
                Admin::updateOrCreate(
                    $uniqueKey,
                    [
                        'name' => $row['name'],
                        'email' => $row['email'] ?? null,
                        'password' => Hash::make($row['password']),
                    ]
                );
                
                $success++;
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        
        return [
            'success' => $success,
            'errors' => $errors,
            'total' => count($data),
        ];
    }

    /**
     * 生徒クラス一括更新（年度切り替え用）
     *
     * CSVフォーマット: seito_id, class_id
     */
    public function importStudentClasses(array $data): array
    {
        $errors = [];
        $skipped = 0;
        $success = 0;

        DB::beginTransaction();

        try {
            foreach ($data as $index => $row) {
                $validator = Validator::make($row, [
                    'seito_id'     => 'required|string',
                    'class_id'     => 'required|string',
                    'seito_number' => 'required|integer|min:1',
                ]);

                if ($validator->fails()) {
                    $errors[] = [
                        'row'    => $index + 2,
                        'data'   => $row,
                        'errors' => $validator->errors()->all(),
                    ];
                    continue;
                }

                // students テーブルに seito_id が存在するか確認
                $student = Student::where('seito_id', $row['seito_id'])->first();

                if (!$student) {
                    $skipped++;
                    continue;
                }

                // classes テーブルに class_id が存在するか確認
                $classExists = ClassModel::where('class_id', $row['class_id'])->exists();

                if (!$classExists) {
                    $skipped++;
                    continue;
                }

                Student::where('seito_id', $row['seito_id'])
                    ->update([
                        'class_id'     => $row['class_id'],
                        'seito_number' => (int) $row['seito_number'],
                    ]);

                $success++;
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'success' => $success,
            'skipped' => $skipped,
            'errors'  => $errors,
            'total'   => count($data),
        ];
    }
}
