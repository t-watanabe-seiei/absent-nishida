<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ParentModel;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /**
     * Classroom認証（ステップ1）
     */
    public function verifyClassroom(Request $request)
    {
        // バリデーション
        $validator = Validator::make($request->all(), [
            'classroom_email' => 'required|email',
            'classroom_password' => 'required|string',
        ], [
            'classroom_email.required' => 'Classroomメールアドレスを入力してください',
            'classroom_email.email' => '有効なメールアドレスを入力してください',
            'classroom_password.required' => 'Classroomパスワードを入力してください',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => '入力内容に誤りがあります',
                'errors' => $validator->errors()
            ], 422);
        }

        // TODO: 実際のClassroom認証ロジックをここに実装
        // 現在は仮実装として、生徒テーブルにメールアドレスが存在するかチェック
        $student = Student::where('seito_initial_email', $request->classroom_email)
            ->orWhere('seito_email', $request->classroom_email)
            ->first();
        
        if (!$student) {
            return response()->json([
                'message' => 'Classroomアカウントが見つかりません',
                'errors' => [
                    'classroom_email' => ['登録されていないメールアドレスです']
                ]
            ], 422);
        }

        // TODO: パスワード検証（実際のClassroom認証）
        // 現在は仮実装として成功を返す

        return response()->json([
            'message' => 'Classroom認証に成功しました',
            'classroom_email' => $request->classroom_email
        ], 200);
    }

    /**
     * 保護者情報登録（ステップ2）
     */
    public function registerParent(Request $request)
    {
        // バリデーション
        $validator = Validator::make($request->all(), [
            'classroom_email' => 'required|email',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'relationship' => 'required|string|max:50',
            'phone' => 'required|string|max:20',
        ], [
            'classroom_email.required' => 'Classroomメールアドレスが必要です',
            'name.required' => 'お名前を入力してください',
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => '有効なメールアドレスを入力してください',
            'email.unique' => 'このメールアドレスは既に登録されています',
            'password.required' => 'パスワードを入力してください',
            'password.min' => 'パスワードは8文字以上で入力してください',
            'password.confirmed' => 'パスワードが一致しません',
            'relationship.required' => '続柄を選択してください',
            'phone.required' => '電話番号を入力してください',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => '入力内容に誤りがあります',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Classroomメールアドレスから生徒を取得
            $student = Student::where('seito_email', $request->classroom_email)
                ->orWhere('seito_initial_email', $request->classroom_email)
                ->first();
            
            if (!$student) {
                return response()->json([
                    'message' => '生徒情報が見つかりません',
                    'errors' => [
                        'general' => ['生徒情報が見つかりません']
                    ]
                ], 422);
            }

            // 保護者アカウントを作成
            $parent = ParentModel::create([
                'seito_id' => $student->seito_id,
                'parent_name' => $request->name,
                'parent_email' => $request->email,
                'parent_password' => $request->password, // モデルでハッシュ化される
                'parent_relationship' => $request->relationship,
                'parent_tel' => $request->phone,
                'parent_initial_email' => $request->classroom_email,
                'parent_initial_password' => '', // Classroomパスワードは保存しない
            ]);

            return response()->json([
                'message' => '登録が完了しました',
                'parent' => $parent
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => '登録に失敗しました',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
