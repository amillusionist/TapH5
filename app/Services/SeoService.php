<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Game;

class SeoService
{
    /**
     * Resolve a canonical absolute URL using the configured SEO URL.
     */
    public function canonicalUrl(string $path = ''): string
    {
        $baseUrl = rtrim((string) config('app.seo_url', config('app.url', 'http://127.0.0.1:8000')), '/');
        $cleanPath = ltrim(parse_url($path, PHP_URL_PATH) ?? '', '/');

        return $cleanPath !== '' ? "{$baseUrl}/{$cleanPath}" : $baseUrl;
    }

    /**
     * Resolve an absolute image URL for Open Graph and Twitter Card tags.
     */
    public function absoluteImageUrl(?string $path = null): string
    {
        if (! $path || $path === '') {
            return $this->canonicalUrl('images/thumbnails/neon-pulse.svg');
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return $this->canonicalUrl(ltrim($path, '/'));
    }

    /**
     * Generate standard BreadcrumbList structured data schema.
     *
     * @param  array<int, array{name: string, url: string}>  $breadcrumbs
     * @return array<string, mixed>
     */
    public function breadcrumbListSchema(array $breadcrumbs): array
    {
        $itemList = [];
        $position = 1;

        foreach ($breadcrumbs as $crumb) {
            $itemList[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $crumb['name'],
                'item' => $crumb['url'],
            ];
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $itemList,
        ];
    }

    /**
     * Generate metadata for the Homepage.
     *
     * @return array<string, mixed>
     */
    public function defaultMeta(): array
    {
        $appName = (string) config('app.name', 'TapH5 Games');
        $canonical = $this->canonicalUrl();
        $logo = $this->absoluteImageUrl('images/thumbnails/neon-pulse.svg');
        $title = "{$appName} — Play Free HTML5 Games Online";
        $description = 'Discover and play high quality HTML5 games on TapH5 Games. Instant gameplay on desktop, tablet, and mobile with zero downloads.';

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => 'index, follow',
            'og_type' => 'website',
            'og_title' => $title,
            'og_description' => $description,
            'og_image' => $logo,
            'og_url' => $canonical,
            'twitter_card' => 'summary_large_image',
            'schema' => [
                '@context' => 'https://schema.org',
                '@graph' => [
                    [
                        '@type' => 'WebSite',
                        '@id' => "{$canonical}#website",
                        'name' => $appName,
                        'url' => $canonical,
                        'description' => $description,
                        'potentialAction' => [
                            '@type' => 'SearchAction',
                            'target' => [
                                '@type' => 'EntryPoint',
                                'urlTemplate' => $this->canonicalUrl('games').'?search={search_term_string}',
                            ],
                            'query-input' => 'required name=search_term_string',
                        ],
                    ],
                    [
                        '@type' => 'Organization',
                        '@id' => "{$canonical}#organization",
                        'name' => $appName,
                        'url' => $canonical,
                        'logo' => $logo,
                    ],
                ],
            ],
        ];
    }

    /**
     * Generate SEO metadata and Schema.org for a single published Game detail page.
     *
     * @return array<string, mixed>
     */
    public function forGame(Game $game): array
    {
        $appName = (string) config('app.name', 'TapH5 Games');
        $canonical = $this->canonicalUrl("games/{$game->slug}");
        $title = $game->seo_title ?: "{$game->title} — Play Free Online Game | {$appName}";

        $description = $game->seo_description;
        if (! $description || trim($description) === '') {
            $rawDesc = (string) ($game->description ?? '');
            $cleanDesc = trim(strip_tags($rawDesc));
            $description = $cleanDesc !== ''
                ? (mb_strlen($cleanDesc) > 155 ? mb_substr($cleanDesc, 0, 152).'...' : $cleanDesc)
                : "Play {$game->title} for free in your browser on {$appName}. Fast, responsive HTML5 gameplay on desktop and mobile.";
        }

        $image = $this->absoluteImageUrl($game->thumbnail);

        $breadcrumbs = [
            ['name' => 'Home', 'url' => $this->canonicalUrl()],
            ['name' => 'Games', 'url' => $this->canonicalUrl('games')],
        ];

        if ($game->category) {
            $breadcrumbs[] = [
                'name' => $game->category->name,
                'url' => $this->canonicalUrl("category/{$game->category->slug}"),
            ];
        }

        $breadcrumbs[] = [
            'name' => $game->title,
            'url' => $canonical,
        ];

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => 'index, follow',
            'og_type' => 'game',
            'og_title' => $title,
            'og_description' => $description,
            'og_image' => $image,
            'og_url' => $canonical,
            'twitter_card' => 'summary_large_image',
            'breadcrumbs' => $breadcrumbs,
            'schema' => [
                '@context' => 'https://schema.org',
                '@graph' => [
                    [
                        '@type' => 'VideoGame',
                        '@id' => "{$canonical}#game",
                        'name' => $game->title,
                        'description' => $description,
                        'image' => $image,
                        'url' => $canonical,
                        'genre' => $game->category?->name ?? 'Arcade',
                        'gamePlatform' => ['Web Browser', 'HTML5', 'Desktop', 'Mobile', 'Tablet'],
                        'applicationCategory' => 'Game',
                        'operatingSystem' => 'Any',
                    ],
                    $this->breadcrumbListSchema($breadcrumbs),
                ],
            ],
        ];
    }

    /**
     * Generate SEO metadata and Schema.org for a Category landing page.
     *
     * @return array<string, mixed>
     */
    public function forCategory(Category $category, bool $isFiltered = false): array
    {
        $appName = (string) config('app.name', 'TapH5 Games');
        $canonical = $this->canonicalUrl("category/{$category->slug}");
        $title = "{$category->name} Games — Free Online {$category->name} Games | {$appName}";
        $description = $category->description
            ? trim($category->description)
            : "Browse and play free online {$category->name} games on {$appName}. High performance HTML5 games for mobile and desktop.";
        $image = $this->absoluteImageUrl($category->thumbnail);

        $breadcrumbs = [
            ['name' => 'Home', 'url' => $this->canonicalUrl()],
            ['name' => 'Categories', 'url' => $this->canonicalUrl('games')],
            ['name' => $category->name, 'url' => $canonical],
        ];

        // Search or custom filter variants should be excluded from search engine indexing
        $robots = $isFiltered ? 'noindex, follow' : 'index, follow';

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $robots,
            'og_type' => 'website',
            'og_title' => $title,
            'og_description' => $description,
            'og_image' => $image,
            'og_url' => $canonical,
            'twitter_card' => 'summary_large_image',
            'breadcrumbs' => $breadcrumbs,
            'schema' => [
                '@context' => 'https://schema.org',
                '@graph' => [
                    [
                        '@type' => 'CollectionPage',
                        '@id' => "{$canonical}#category",
                        'name' => "{$category->name} Games",
                        'description' => $description,
                        'url' => $canonical,
                    ],
                    $this->breadcrumbListSchema($breadcrumbs),
                ],
            ],
        ];
    }

    /**
     * Generate SEO metadata for the Games Catalog listing page.
     *
     * @return array<string, mixed>
     */
    public function forGamesIndex(?string $search = null, ?string $categorySlug = null, ?string $sort = null, ?Category $category = null): array
    {
        $appName = (string) config('app.name', 'TapH5 Games');
        $canonical = $this->canonicalUrl('games');

        $hasSearch = ! empty($search);
        $hasCustomSort = ! empty($sort) && $sort !== 'newest';

        if ($hasSearch) {
            $title = "Search: \"{$search}\" — Free Games | {$appName}";
            $description = "Search results for {$search} free HTML5 games on {$appName}. Instant in-browser play.";
            $robots = 'noindex, follow';
        } elseif ($category) {
            $title = "{$category->name} Games — Free Online Games | {$appName}";
            $description = "Play free {$category->name} games online on {$appName}.";
            $robots = 'index, follow';
        } else {
            $title = "All Free HTML5 Games — Instant Browser Play | {$appName}";
            $description = "Explore and play all free HTML5 games on {$appName}. Filter by genre, search favorites, and play instantly in any browser.";
            $robots = $hasCustomSort ? 'noindex, follow' : 'index, follow';
        }

        $breadcrumbs = [
            ['name' => 'Home', 'url' => $this->canonicalUrl()],
            ['name' => 'Games', 'url' => $canonical],
        ];

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $robots,
            'og_type' => 'website',
            'og_title' => $title,
            'og_description' => $description,
            'og_image' => $this->absoluteImageUrl('images/thumbnails/neon-pulse.svg'),
            'og_url' => $canonical,
            'twitter_card' => 'summary_large_image',
            'breadcrumbs' => $breadcrumbs,
            'schema' => [
                '@context' => 'https://schema.org',
                '@graph' => [
                    [
                        '@type' => 'CollectionPage',
                        '@id' => "{$canonical}#catalog",
                        'name' => 'Free HTML5 Games Catalog',
                        'description' => $description,
                        'url' => $canonical,
                    ],
                    $this->breadcrumbListSchema($breadcrumbs),
                ],
            ],
        ];
    }

    /**
     * Generate SEO metadata for static informational pages.
     *
     * @param  array<int, array{name: string, url: string}>  $breadcrumbs
     * @return array<string, mixed>
     */
    public function forPage(string $title, string $description, string $path, string $robots = 'index, follow', array $breadcrumbs = []): array
    {
        $appName = (string) config('app.name', 'TapH5 Games');
        $canonical = $this->canonicalUrl($path);
        $fullTitle = "{$title} — {$appName}";
        $image = $this->absoluteImageUrl('images/thumbnails/neon-pulse.svg');

        if (empty($breadcrumbs)) {
            $breadcrumbs = [
                ['name' => 'Home', 'url' => $this->canonicalUrl()],
                ['name' => $title, 'url' => $canonical],
            ];
        }

        return [
            'title' => $fullTitle,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $robots,
            'og_type' => 'website',
            'og_title' => $fullTitle,
            'og_description' => $description,
            'og_image' => $image,
            'og_url' => $canonical,
            'twitter_card' => 'summary_large_image',
            'breadcrumbs' => $breadcrumbs,
            'schema' => [
                '@context' => 'https://schema.org',
                '@graph' => [
                    [
                        '@type' => 'WebPage',
                        '@id' => "{$canonical}#webpage",
                        'name' => $title,
                        'description' => $description,
                        'url' => $canonical,
                    ],
                    $this->breadcrumbListSchema($breadcrumbs),
                ],
            ],
        ];
    }
}
