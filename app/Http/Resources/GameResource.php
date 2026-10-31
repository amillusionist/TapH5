<?php

namespace App\Http\Resources;

use App\Models\Game;
use App\Services\AnalyticsService;
use App\Services\GameUrlService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Game
 */
class GameResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $gameUrlService = app(GameUrlService::class);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'instructions' => $this->instructions,
            'thumbnail' => $this->thumbnail,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn () => $this->category ? (new CategoryResource($this->category))->resolve() : null),
            'tags' => $this->whenLoaded('tags', fn () => TagResource::collection($this->tags)->resolve()),
            'game_type' => $this->game_type,
            'entry_file' => $this->entry_file,
            'storage_path' => $this->storage_path,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'is_trending' => $this->is_trending,
            'is_popular' => $this->is_popular,
            'plays_count' => $this->plays_count,
            'rating' => $this->rating ?? null,
            'rating_count' => (int) ($this->rating_count ?? 0),
            'playing_now' => (int) ($this->playing_now ?? (app(AnalyticsService::class)->getCachedActivePlayersCounts()[$this->id] ?? 0)),
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'embed_url' => $gameUrlService->getEmbedUrl($this->resource),
            'sandbox_attributes' => $gameUrlService->getSandboxAttributes(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
