<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_xml_sitemap_returns_valid_xml_with_routes_categories_and_games(): void
    {
        $category = Category::factory()->create(['slug' => 'arcade', 'status' => 'active']);
        $game = Game::factory()->create([
            'slug' => 'test-game',
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml');

        $content = $response->getContent();
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $content);
        $this->assertStringContainsString('<urlset', $content);
        $this->assertStringContainsString('/games/test-game', $content);
        $this->assertStringContainsString('/category/arcade', $content);
    }
}
