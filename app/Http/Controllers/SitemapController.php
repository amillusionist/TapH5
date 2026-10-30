<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Game;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $categories = Category::active()->get();
        $games = Game::published()->get();

        $staticPages = [
            ['url' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'],
            ['url' => route('games.index'), 'priority' => '0.9', 'changefreq' => 'daily'],
            ['url' => route('pages.about'), 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['url' => route('pages.contact'), 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['url' => route('pages.privacy'), 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['url' => route('pages.terms'), 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['url' => route('pages.cookie-policy'), 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['url' => route('pages.data-policy'), 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($staticPages as $page) {
            $xml .= '<url>';
            $xml .= '<loc>'.htmlspecialchars($page['url']).'</loc>';
            $xml .= '<changefreq>'.$page['changefreq'].'</changefreq>';
            $xml .= '<priority>'.$page['priority'].'</priority>';
            $xml .= '</url>';
        }

        foreach ($categories as $category) {
            $xml .= '<url>';
            $xml .= '<loc>'.htmlspecialchars(route('categories.show', ['slug' => $category->slug])).'</loc>';
            $xml .= '<lastmod>'.$category->updated_at?->toAtomString().'</lastmod>';
            $xml .= '<changefreq>weekly</changefreq>';
            $xml .= '<priority>0.8</priority>';
            $xml .= '</url>';
        }

        foreach ($games as $game) {
            $xml .= '<url>';
            $xml .= '<loc>'.htmlspecialchars(route('games.show', ['slug' => $game->slug])).'</loc>';
            $xml .= '<lastmod>'.$game->updated_at?->toAtomString().'</lastmod>';
            $xml .= '<changefreq>weekly</changefreq>';
            $xml .= '<priority>0.8</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
