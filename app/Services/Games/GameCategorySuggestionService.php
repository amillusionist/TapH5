<?php

namespace App\Services\Games;

use App\Models\Category;

class GameCategorySuggestionService
{
    /**
     * Map of common gaming keywords associated with standard category slugs.
     *
     * @var array<string, list<string>>
     */
    protected array $keywordMap = [
        'arcade' => ['arcade', 'retro', 'classic', 'pixel', '8bit', 'pacman', 'brick', 'pinball', 'pong', 'flappy'],
        'action' => ['action', 'fight', 'ninja', 'combat', 'battle', 'strike', 'war', 'survival', 'sword', 'stealth'],
        'puzzle' => ['puzzle', 'brain', 'logic', 'match', 'match3', 'connect', 'block', 'sudoku', 'trivia', 'quiz', 'word', 'card'],
        'racing' => ['racing', 'race', 'drift', 'speed', 'car', 'drive', 'driving', 'track', 'kart', 'motor', 'highway'],
        'adventure' => ['adventure', 'quest', 'rpg', 'dungeon', 'explore', 'story', 'journey', 'hero', 'escape'],
        'sports' => ['sports', 'sport', 'basketball', 'hoops', 'football', 'soccer', 'golf', 'tennis', 'baseball', 'cricket', 'bowling'],
        'shooter' => ['shooter', 'shoot', 'gun', 'sniper', 'blaster', 'fps', 'defense', 'tank', 'space shooter'],
    ];

    /**
     * Suggest an appropriate category based on game text analysis.
     *
     * @return array{category: Category|null, confidence: float, matched_keywords: list<string>}
     */
    public function suggest(string $title, string $folderName, string $description = ''): array
    {
        $categories = Category::active()->get();
        if ($categories->isEmpty()) {
            return [
                'category' => null,
                'confidence' => 0.0,
                'matched_keywords' => [],
            ];
        }

        $searchCorpus = strtolower("{$title} {$folderName} {$description}");

        $bestScore = 0;
        $bestCategory = null;
        $bestMatches = [];

        foreach ($categories as $category) {
            $catSlug = strtolower($category->slug);
            $catName = strtolower($category->name);

            $keywords = $this->keywordMap[$catSlug] ?? [$catSlug, $catName];
            if (! in_array($catSlug, $keywords, true)) {
                $keywords[] = $catSlug;
            }
            if (! in_array($catName, $keywords, true)) {
                $keywords[] = $catName;
            }

            $matches = [];
            $score = 0;

            foreach ($keywords as $keyword) {
                if (preg_match('/\b'.preg_quote($keyword, '/').'\b/i', $searchCorpus)) {
                    $matches[] = $keyword;
                    // Exact category name match in title has high weight
                    if (str_contains(strtolower($title), $keyword)) {
                        $score += 3;
                    } elseif (str_contains(strtolower($folderName), $keyword)) {
                        $score += 2;
                    } else {
                        $score += 1;
                    }
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestCategory = $category;
                $bestMatches = $matches;
            }
        }

        // Only assign if confidence threshold is met (score >= 2)
        if ($bestScore >= 2 && $bestCategory !== null) {
            $confidence = min(1.0, 0.4 + ($bestScore * 0.15));

            return [
                'category' => $bestCategory,
                'confidence' => round($confidence, 2),
                'matched_keywords' => $bestMatches,
            ];
        }

        // Low confidence: leave category unassigned as required by Phase 2 rules
        return [
            'category' => null,
            'confidence' => 0.0,
            'matched_keywords' => [],
        ];
    }
}
