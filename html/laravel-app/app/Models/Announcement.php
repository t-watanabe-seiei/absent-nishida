<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'title',
        'body',
        'target_class_ids',
        'target_parent_ids',
        'notify_by_email',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'target_class_ids'  => 'array',
            'target_parent_ids' => 'array',
            'notify_by_email'   => 'boolean',
            'expires_at'        => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(AnnouncementAttachment::class);
    }

    /**
     * 有効期限内のお知らせのみ返すスコープ
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', Carbon::now());
    }
}
