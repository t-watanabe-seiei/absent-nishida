<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreParentRequest;
use App\Http\Requests\UpdateParentRequest;
use App\Models\ParentModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ParentController extends Controller
{
    /**
     * 保護者一覧取得
     */
    public function index(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $query = ParentModel::with('student.classModel');

        // 担任の場合は自分のクラスの保護者のみ表示
        if ($admin && !$admin->is_super_admin && $admin->class_id) {
            $query->whereHas('student', function ($q) use ($admin) {
                $q->where('class_id', $admin->class_id);
            });
        }

        // 検索
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('parent_name', 'like', "%{$search}%")
                  ->orWhere('parent_email', 'like', "%{$search}%")
                  ->orWhere('seito_id', 'like', "%{$search}%");
            });
        }

        // 生徒IDでフィルタ
        if ($request->has('seito_id')) {
            $query->where('seito_id', $request->seito_id);
        }

        // ページネーション
        $parents = $query->orderBy('seito_id')
                        ->paginate($request->get('per_page', 20));

        return response()->json($parents);
    }

    /**
     * 保護者登録
     */
    public function store(StoreParentRequest $request)
    {
        $data = $request->validated();
        
        // 初期パスワードを生成して保存
        $initialPassword = Str::random(12);
        $data['parent_initial_email'] = $data['parent_email'];
        $data['parent_initial_password'] = $initialPassword;
        
        // パスワードはハッシュ化（自動的にキャストでハッシュ化される）
        $parent = ParentModel::create($data);
        $parent->load('student.classModel');

        return response()->json([
            'message' => '保護者を登録しました',
            'parent' => $parent,
            'initial_password' => $initialPassword, // 初回のみ返却
        ], 201);
    }

    /**
     * 保護者詳細取得
     */
    public function show($id)
    {
        $parent = ParentModel::with('student.classModel')
                           ->findOrFail($id);

        return response()->json($parent);
    }

    /**
     * 保護者更新
     */
    public function update(UpdateParentRequest $request, $id)
    {
        $parent = ParentModel::findOrFail($id);
        $data = $request->validated();
        
        // パスワードが送信されていない場合は更新しない
        if (empty($data['parent_password'])) {
            unset($data['parent_password']);
        }
        
        $parent->update($data);
        $parent->load('student.classModel');

        return response()->json([
            'message' => '保護者を更新しました',
            'parent' => $parent,
        ]);
    }

    /**
     * 保護者削除
     */
    public function destroy($id)
    {
        $parent = ParentModel::findOrFail($id);
        $parent->delete();

        return response()->json([
            'message' => '保護者を削除しました',
        ]);
    }
}
