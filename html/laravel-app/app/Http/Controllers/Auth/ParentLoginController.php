<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ParentModel;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ParentLoginController extends Controller
{
    protected $twoFactorService;

    public function __construct(TwoFactorService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * 保護者ログイン（parent_initial_email/parent_initial_passwordで認証）
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // parent_initial_emailで保護者を検索
        $parent = ParentModel::where('parent_initial_email', $credentials['email'])->first();

        if (!$parent) {
            throw ValidationException::withMessages([
                'email' => ['初期メールアドレスまたはパスワードが正しくありません。'],
            ]);
        }

        // parent_initial_passwordで認証（自動ハッシュ化対応）
        if (!Hash::check($credentials['password'], $parent->parent_initial_password)) {
            throw ValidationException::withMessages([
                'email' => ['初期メールアドレスまたはパスワードが正しくありません。'],
            ]);
        }

        // parent_emailが未登録の場合、メール登録が必要
        if (empty($parent->parent_email)) {
            // セッションに一時的にparent_idを保存
            $request->session()->put('parent_id_pending', $parent->id);
            
            return response()->json([
                'message' => '初回ログインです。保護者用のメールアドレスを登録してください。',
                'requires_email_registration' => true,
                'parent_id' => $parent->id,
                'parent_name' => $parent->parent_name,
            ]);
        }

        // parent_emailが登録済みの場合、2段階認証コードを送信
        $sent = $this->twoFactorService->createAndSend(
            $parent->parent_email,
            'parent',
            $parent->parent_name
        );

        if (!$sent) {
            throw ValidationException::withMessages([
                'email' => ['認証コードの送信に失敗しました。しばらくしてから再度お試しください。'],
            ]);
        }

        // セッションに一時的にparent_idを保存（2FA検証用）
        $request->session()->put('parent_id_pending', $parent->id);

        return response()->json([
            'message' => '認証コードをメールで送信しました。',
            'requires_2fa' => true,
            'email' => $parent->parent_email,
        ]);
    }

    /**
     * 保護者用メールアドレス登録（初回ログイン時のみ）
     */
    public function registerEmail(Request $request)
    {
        $request->validate([
            'parent_email' => 'required|email|unique:parents,parent_email',
        ]);

        // セッションからparent_idを取得
        $parentId = $request->session()->get('parent_id_pending');
        
        if (!$parentId) {
            throw ValidationException::withMessages([
                'parent_email' => ['セッションが無効です。再度ログインしてください。'],
            ]);
        }

        $parent = ParentModel::find($parentId);

        if (!$parent) {
            throw ValidationException::withMessages([
                'parent_email' => ['保護者情報が見つかりません。'],
            ]);
        }

        // parent_emailを登録
        $parent->parent_email = $request->parent_email;
        $parent->save();

        // 2段階認証コードを送信
        $sent = $this->twoFactorService->createAndSend(
            $parent->parent_email,
            'parent',
            $parent->parent_name
        );

        if (!$sent) {
            throw ValidationException::withMessages([
                'parent_email' => ['認証コードの送信に失敗しました。'],
            ]);
        }

        return response()->json([
            'message' => 'メールアドレスを登録しました。認証コードを送信しました。',
            'requires_2fa' => true,
            'email' => $parent->parent_email,
        ]);
    }

    /**
     * 2FA検証
     */
    public function verify2FA(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        // セッションからparent_idを取得
        $parentId = $request->session()->get('parent_id_pending');
        
        if (!$parentId) {
            throw ValidationException::withMessages([
                'code' => ['セッションが無効です。再度ログインしてください。'],
            ]);
        }

        $parent = ParentModel::find($parentId);

        if (!$parent || empty($parent->parent_email)) {
            throw ValidationException::withMessages([
                'code' => ['保護者情報が見つかりません。'],
            ]);
        }

        // 2段階認証コード検証
        if (!$this->twoFactorService->verify($parent->parent_email, $request->code, 'parent')) {
            throw ValidationException::withMessages([
                'code' => ['認証コードが正しくありません。'],
            ]);
        }

        // 2段階認証成功 → 本ログイン
        Auth::guard('parent')->login($parent);
        $request->session()->regenerate();
        $request->session()->forget('parent_id_pending');

        return response()->json([
            'message' => 'ログインしました。',
            'parent' => [
                'id' => $parent->id,
                'name' => $parent->parent_name,
                'email' => $parent->parent_email,
                'seito_id' => $parent->seito_id,
            ],
        ]);
    }

    /**
     * 2FA認証コード再送信
     */
    public function resend2FA(Request $request)
    {
        // セッションからparent_idを取得
        $parentId = $request->session()->get('parent_id_pending');
        
        if (!$parentId) {
            throw ValidationException::withMessages([
                'email' => ['セッションが無効です。再度ログインしてください。'],
            ]);
        }

        $parent = ParentModel::find($parentId);

        if (!$parent || empty($parent->parent_email)) {
            throw ValidationException::withMessages([
                'email' => ['保護者情報が見つかりません。'],
            ]);
        }

        $sent = $this->twoFactorService->createAndSend(
            $parent->parent_email,
            'parent',
            $parent->parent_name
        );

        if (!$sent) {
            throw ValidationException::withMessages([
                'email' => ['認証コードの送信に失敗しました。'],
            ]);
        }

        return response()->json([
            'message' => '認証コードを再送信しました。',
        ]);
    }

    /**
     * ログアウト
     */
    public function logout(Request $request)
    {
        Auth::guard('parent')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'ログアウトしました。']);
    }

    /**
     * 現在のログイン保護者情報取得
     */
    public function me(Request $request)
    {
        $parent = Auth::guard('parent')->user();

        if (!$parent) {
            return response()->json(['message' => '認証されていません。'], 401);
        }

        return response()->json([
            'id' => $parent->id,
            'name' => $parent->parent_name,
            'email' => $parent->parent_email,
            'initial_email' => $parent->parent_initial_email,
            'tel' => $parent->parent_tel,
            'relationship' => $parent->parent_relationship,
            'seito_id' => $parent->seito_id,
        ]);
    }
}
