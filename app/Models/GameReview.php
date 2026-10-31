<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $game_id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $from_status
 * @property string|null $to_status
 * @property string|null $notes
 * @property array<string, mixed>|null $metadata
 * @property Carbon $created_at
 * @property-read Game $game
 * @property-read User|null $user
 */
class GameReview extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    public const ACTION_SUBMITTED = 'submitted';

    public const ACTION_APPROVED = 'approved';

    public const ACTION_REJECTED = 'rejected';

    public const ACTION_CHANGES_REQUESTED = 'changes_requested';

    public const ACTION_RETURNED_TO_DRAFT = 'returned_to_draft';

    public const ACTION_PUBLISHED = 'published';

    public const ACTION_PACKAGE_REPLACED = 'package_replaced';

    public const ACTION_RESCANNED = 'rescanned';

    public const ACTION_THUMBNAIL_REGENERATED = 'thumbnail_regenerated';

    public const ACTION_METADATA_REFRESHED = 'metadata_refreshed';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'game_id',
        'user_id',
        'action',
        'from_status',
        'to_status',
        'notes',
        'metadata',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
