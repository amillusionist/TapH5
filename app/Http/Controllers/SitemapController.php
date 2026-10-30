<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Game;
use App\Services\SeoService;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(SeoService $seoService): Response
    {
        $categories = Category::active()
            ->has('publishedGames')
            ->select(['slug', 'updated_at'])
            ->get();

        $games = Game::published()
            ->select(['slug', 'updated_at'])
            ->latest('updated_at')
            ->get();

        $staticPaths = [
            '',
            'games',
            'about',
            'contact',
            'privacy',
            'terms',
            'cookie-policy',
            'data-policy',
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($staticPaths as $path) {
            $xml .= '<url>';
            $xml .= '<loc>'.htmlspecialchars($seoService->canonicalUrl($path)).'</loc>';
            $xml .= '</url>';
        }

        foreach ($categories as $category) {
            $xml .= '<url>';
            $xml .= '<loc>'.htmlspecialchars($seoService->canonicalUrl("category/{$category->slug}")).'</loc>';
            if ($category->updated_at) {
                $xml .= '<lastmod>'.$category->updated_at->toAtomString().'</lastmod>';
            }
            $xml .= '</url>';
        }

        foreach ($games as $game) {
            $xml .= '<url>';
            $xml .= '<loc>'.htmlspecialchars($seoService->canonicalUrl("games/{$game->slug}")).'</loc>';
            if ($game->updated_at) {
                $xml .= '<lastmod>'.$game->updated_at->toAtomString().'</lastmod>';
            }
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
