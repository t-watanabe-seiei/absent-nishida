<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    /**
     * システム設定一覧取得（スーパー管理者のみ）
     */
    public function index()
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin->is_super_admin) {
            return response()->json(['error' => 'スーパー管理者のみアクセス可能です'], 403);
        }

        $settings = SystemSetting::all()->pluck('value', 'key');

        return response()->json($settings);
    }

    /**
     * システム設定更新（スーパー管理者のみ）
     */
    public function update(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin->is_super_admin) {
            return response()->json(['error' => 'スーパー管理者のみ設定を変更できます'], 403);
        }

        $validated = $request->validate([
            'announcement_enabled' => 'required|in:0,1',
        ]);

        SystemSetting::setValue('announcement_enabled', (string) $validated['announcement_enabled']);

        return response()->json(['message' => '設定を保存しました']);
    }
}
