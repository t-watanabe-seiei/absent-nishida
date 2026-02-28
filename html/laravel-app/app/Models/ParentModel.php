<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class ParentModel extends Authenticatable
{
    use HasFactory;
    protected $table = 'parents';

    protected $fillable = [
        'seito_id',
        'parent_name',
        'parent_relationship',
        'parent_tel',
        'parent_initial_email',
        'parent_initial_password',
        'parent_email',
        'parent_password',
    ];

    protected $hidden = [
        'parent_initial_password', // ログイン用パスワードを非表示
        'parent_password',
    ];

    protected function casts(): array
    {
        return [
            'parent_initial_password' => 'hashed', // ログイン用パスワードをハッシュ化
            'parent_password' => 'hashed', // 将来の拡張用
        ];
    }

    public function getAuthPassword()
    {
        return $this->parent_initial_password; // 認証にはparent_initial_passwordを使用
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'seito_id', 'seito_id');
    }
}
