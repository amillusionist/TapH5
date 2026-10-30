<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $date
 * @property int $game_id
 * @property int $views_count
 * @property int $play_clicks_count
 * @property int $game_loads_count
 * @property int $unique_players_count
 * @property int $total_engagement_seconds
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Game $game
 */
class AnalyticsDailyAggregate extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'date',
        'game_id',
        'views_count',
        'play_clicks_count',
        'game_loads_count',
        'unique_players_count',
        'total_engagement_seconds',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'views_count' => 'integer',
            'play_clicks_count' => 'integer',
            'game_loads_count' => 'integer',
            'unique_players_count' => 'integer',
            'total_engagement_seconds' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}

