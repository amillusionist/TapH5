<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string|null $user_email
 * @property string $action
 * @property string|null $resource_type
 * @property string|null $resource_id
 * @property string $description
 * @property array<string, mixed>|null $details
 * @property string|null $ip_address
 * @property Carbon $created_at
 */
class AdminAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_email',
        'action',
        'resource_type',
        'resource_id',
        'description',
        'details',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }
}
