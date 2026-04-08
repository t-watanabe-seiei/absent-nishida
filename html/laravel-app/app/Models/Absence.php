<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Absence extends Model
{
    use HasFactory;
    protected $fillable = [
        'seito_id',
        'division',
        'reason',
        'scheduled_time',
        'absence_date',
        'is_deleted',
        'deleted_at',
        'is_admin_created',
    ];

    protected function casts(): array
    {
        return [
            'absence_date' => 'date',
            'is_deleted' => 'boolean',
            'deleted_at' => 'datetime',
            'is_admin_created' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'seito_id', 'seito_id');
    }
}
