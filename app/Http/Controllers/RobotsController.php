<?php

namespace App\Http\Controllers;

use App\Services\SeoService;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(SeoService $seoService): Response
    {
        $sitemapUrl = $seoService->canonicalUrl('sitemap.xml');

        $content = "User-agent: *\n"
            ."Disallow: /admin/\n"
            ."Disallow: /dashboard\n"
            ."Disallow: /login\n"
            ."Disallow: /register\n"
            ."Disallow: /profile\n\n"
            ."Sitemap: {$sitemapUrl}\n";

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}
