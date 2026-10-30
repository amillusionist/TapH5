<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Game;
use Illuminate\Support\Facades\URL;

class SeoService
{
    /**
     * @return array<string, mixed>
     */
    public function defaultMeta(): array
    {
        $appName = config('app.name', 'TapH5 Games');
        $canonical = URL::to('/');

        return [
            'title' => "{$appName} — Play Free HTML5 Games Online",
            'description' => 'Discover and play high quality HTML5 games on TapH5 Games. Instant gameplay on desktop, tablet, and mobile with zero downloads.',
            'canonical' => $canonical,
            'og_type' => 'website',
            'og_title' => "{$appName} — Free Instant HTML5 Gaming",
            'og_description' => 'Play the best online HTML5 action, arcade, puzzle, racing, and adventure games.',
            'og_image' => URL::to('/images/thumbnails/neon-pulse.svg'),
            'og_url' => $canonical,
            'twitter_card' => 'summary_large_image',
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => $appName,
                'url' => $canonical,
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => [
                        '@type' => 'EntryPoint',
                        'urlTemplate' => URL::route('games.index').'?search={search_term_string}',
                    ],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forGame(Game $game): array
    {
        $appName = config('app.name', 'TapH5 Games');
        $canonical = URL::route('games.show', ['slug' => $game->slug]);
        $title = $game->seo_title ?: "{$game->title} — Play Free Online Game | {$appName}";
        $description = $game->seo_description ?: ($game->description ? substr(strip_tags($game->description), 0, 160) : "Play {$game->title} instantly on {$appName}. Free browser game with no downloads.");
        $image = $game->thumbnail ? (str_starts_with($game->thumbnail, 'http') ? $game->thumbnail : URL::to($game->thumbnail)) : URL::to('/images/thumbnails/neon-pulse.svg');

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'og_type' => 'game',
            'og_title' => $title,
            'og_description' => $description,
            'og_image' => $image,
            'og_url' => $canonical,
            'twitter_card' => 'summary_large_image',
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'VideoGame',
                'name' => $game->title,
                'description' => $description,
                'image' => $image,
                'url' => $canonical,
                'genre' => $game->category?->name ?? 'Arcade',
                'gamePlatform' => ['Web Browser', 'HTML5', 'Mobile', 'Desktop'],
                'applicationCategory' => 'Game',
                'operatingSystem' => 'Any',
                'offers' => [
                    '@type' => 'Offer',
                    'price' => '0',
                    'priceCurrency' => 'USD',
                    'availability' => 'https://schema.org/InStock',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forCategory(Category $category): array
    {
        $appName = config('app.name', 'TapH5 Games');
        $canonical = URL::route('categories.show', ['slug' => $category->slug]);
        $title = "{$category->name} Games — Free Online {$category->name} Games | {$appName}";
        $description = $category->description ?: "Browse and play free online {$category->name} games on {$appName}. High performance HTML5 games for mobile and desktop.";
        $image = $category->thumbnail ? (str_starts_with($category->thumbnail, 'http') ? $category->thumbnail : URL::to($category->thumbnail)) : URL::to('/images/thumbnails/neon-pulse.svg');

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'og_type' => 'website',
            'og_title' => $title,
            'og_description' => $description,
            'og_image' => $image,
            'og_url' => $canonical,
            'twitter_card' => 'summary_large_image',
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                'name' => "{$category->name} Games",
                'description' => $description,
                'url' => $canonical,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forPage(string $title, string $description, string $route): array
    {
        $appName = config('app.name', 'TapH5 Games');
        $canonical = URL::to($route);
        $fullTitle = "{$title} — {$appName}";

        return [
            'title' => $fullTitle,
            'description' => $description,
            'canonical' => $canonical,
            'og_type' => 'website',
            'og_title' => $fullTitle,
            'og_description' => $description,
            'og_image' => URL::to('/images/thumbnails/neon-pulse.svg'),
            'og_url' => $canonical,
            'twitter_card' => 'summary_large_image',
            'schema' => [
                '@context' => 'https://schema.org',
                '@type' => 'WebPage',
                'name' => $title,
                'description' => $description,
                'url' => $canonical,
            ],
        ];
    }
}
