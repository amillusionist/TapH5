<?php

namespace App\Services\Games;

use Illuminate\Support\Str;

class GameMetadataExtractor
{
    /**
     * Statically extract metadata from a game's entry HTML file.
     *
     * @param  string  $entryFilePath  Absolute path to the entry HTML file
     * @param  string  $folderName  Fallback folder name for title derivation
     * @return array{
     *     title: string,
     *     slug: string,
     *     description: string,
     *     seo_title: string,
     *     seo_description: string,
     *     og_image: string|null,
     *     twitter_image: string|null,
     *     icon: string|null,
     *     warnings: list<string>
     * }
     */
    public function extract(string $entryFilePath, string $folderName): array
    {
        $warnings = [];

        $htmlContent = '';
        if (is_file($entryFilePath) && is_readable($entryFilePath)) {
            // Read first 256KB of entry file - sufficient for <head> and meta tags without high memory
            $fp = fopen($entryFilePath, 'rb');
            if ($fp !== false) {
                $htmlContent = fread($fp, 262144) ?: '';
                fclose($fp);
            }
        }

        $rawTitle = null;
        $rawDescription = null;
        $ogTitle = null;
        $ogDescription = null;
        $ogImage = null;
        $twitterImage = null;
        $icon = null;

        if (! empty($htmlContent)) {
            // Extract <title>...</title>
            if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $htmlContent, $matches)) {
                $rawTitle = html_entity_decode(trim(strip_tags($matches[1])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            // Extract <meta name="description" content="...">
            if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']*)["\']/i', $htmlContent, $matches)) {
                $rawDescription = html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            } elseif (preg_match('/<meta[^>]+content=["\']([^"\']*)["\'][^>]+name=["\']description["\']/i', $htmlContent, $matches)) {
                $rawDescription = html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            // Extract <meta property="og:title" content="...">
            if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']*)["\']/i', $htmlContent, $matches)) {
                $ogTitle = html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            // Extract <meta property="og:description" content="...">
            if (preg_match('/<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']*)["\']/i', $htmlContent, $matches)) {
                $ogDescription = html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            // Extract <meta property="og:image" content="...">
            if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']*)["\']/i', $htmlContent, $matches)) {
                $ogImage = trim($matches[1]);
            }

            // Extract <meta name="twitter:image" content="...">
            if (preg_match('/<meta[^>]+name=["\']twitter:image["\'][^>]+content=["\']([^"\']*)["\']/i', $htmlContent, $matches)) {
                $twitterImage = trim($matches[1]);
            }

            // Extract <link rel="icon" ... href="...">
            if (preg_match('/<link[^>]+rel=["\'](?:shortcut )?icon["\'][^>]+href=["\']([^"\']*)["\']/i', $htmlContent, $matches)) {
                $icon = trim($matches[1]);
            } elseif (preg_match('/<link[^>]+href=["\']([^"\']*)["\'][^>]+rel=["\'](?:shortcut )?icon["\']/i', $htmlContent, $matches)) {
                $icon = trim($matches[1]);
            }
        }

        // Title resolution
        $finalTitle = $ogTitle ?: $rawTitle;
        if (blank($finalTitle)) {
            // Derive clean title from folder name
            $finalTitle = ucwords(str_replace(['-', '_', '.'], ' ', $folderName));
            $warnings[] = "HTML title missing; derived '{$finalTitle}' from folder name.";
        }

        // Slug resolution
        $slug = Str::slug($finalTitle);
        if (blank($slug)) {
            $slug = Str::slug($folderName);
        }

        // Description resolution
        $finalDescription = $ogDescription ?: $rawDescription;
        if (blank($finalDescription)) {
            $finalDescription = "Play {$finalTitle} online for free on TapH5 Games. Fast, responsive HTML5 gameplay in your browser.";
            $warnings[] = 'HTML meta description missing; applied placeholder description.';
        }

        // SEO meta
        $seoTitle = "{$finalTitle} — Play Free Online HTML5 Game | TapH5";
        $seoDescription = Str::limit(strip_tags($finalDescription), 160);

        return [
            'title' => $finalTitle,
            'slug' => $slug,
            'description' => $finalDescription,
            'seo_title' => $seoTitle,
            'seo_description' => $seoDescription,
            'og_image' => $ogImage,
            'twitter_image' => $twitterImage,
            'icon' => $icon,
            'warnings' => $warnings,
        ];
    }
}
