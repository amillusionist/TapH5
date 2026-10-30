<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_has_correct_seo_meta_and_structured_data(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Home')
            ->has('meta', fn ($meta) => $meta
                ->where('robots', 'index, follow')
                ->where('og_type', 'website')
                ->has('canonical')
                ->has('title')
                ->has('description')
                ->has('schema.@graph')
                ->etc()
            )
        );
    }

    public function test_game_detail_page_has_dynamic_seo_and_videogame_schema_without_fake_offers(): void
    {
        $category = Category::factory()->create([
            'name' => 'Puzzle',
            'slug' => 'puzzle',
            'status' => 'active',
        ]);

        $game = Game::factory()->create([
            'title' => 'Brain Teaser',
            'slug' => 'brain-teaser',
            'description' => 'A challenging puzzle game designed for clever minds.',
            'category_id' => $category->id,
            'status' => 'published',
            'seo_title' => 'Custom SEO Title for Brain Teaser',
            'seo_description' => 'Custom meta description for search engines.',
        ]);

        $response = $this->get('/games/brain-teaser');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Games/Show')
            ->has('meta', fn ($meta) => $meta
                ->where('title', 'Custom SEO Title for Brain Teaser')
                ->where('description', 'Custom meta description for search engines.')
                ->where('robots', 'index, follow')
                ->where('og_type', 'game')
                ->where('breadcrumbs.2.name', 'Puzzle')
                ->where('breadcrumbs.3.name', 'Brain Teaser')
                ->has('schema.@graph')
                ->where('schema.@graph.0.@type', 'VideoGame')
                ->where('schema.@graph.0.name', 'Brain Teaser')
                ->missing('schema.@graph.0.offers') // strictly no fake price/offers
                ->where('schema.@graph.1.@type', 'BreadcrumbList')
                ->etc()
            )
        );
    }

    public function test_category_page_has_collection_page_and_breadcrumbs_schema(): void
    {
        $category = Category::factory()->create([
            'name' => 'Racing',
            'slug' => 'racing',
            'status' => 'active',
        ]);

        Game::factory()->create([
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $response = $this->get('/category/racing');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Categories/Show')
            ->has('meta', fn ($meta) => $meta
                ->where('robots', 'index, follow')
                ->where('schema.@graph.0.@type', 'CollectionPage')
                ->where('schema.@graph.1.@type', 'BreadcrumbList')
                ->etc()
            )
        );
    }

    public function test_games_index_with_search_applies_noindex_follow(): void
    {
        $response = $this->get('/games?search=racing');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Games/Index')
            ->where('meta.robots', 'noindex, follow')
        );
    }

    public function test_category_page_with_search_applies_noindex_follow(): void
    {
        $category = Category::factory()->create([
            'name' => 'Sports',
            'slug' => 'sports',
            'status' => 'active',
        ]);

        $response = $this->get('/category/sports?search=soccer');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Categories/Show')
            ->where('meta.robots', 'noindex, follow')
        );
    }

    public function test_draft_and_archived_games_return_404_on_public_routes(): void
    {
        $category = Category::factory()->create(['status' => 'active']);

        $draftGame = Game::factory()->create([
            'slug' => 'draft-game',
            'category_id' => $category->id,
            'status' => 'draft',
        ]);

        $archivedGame = Game::factory()->create([
            'slug' => 'archived-game',
            'category_id' => $category->id,
            'status' => 'archived',
        ]);

        $this->get('/games/draft-game')->assertNotFound();
        $this->get('/games/archived-game')->assertNotFound();
        $this->get('/games/non-existent-game')->assertNotFound();
    }

    public function test_inactive_categories_return_404(): void
    {
        Category::factory()->create([
            'slug' => 'inactive-category',
            'status' => 'inactive',
        ]);

        $this->get('/category/inactive-category')->assertNotFound();
    }

    public function test_sitemap_excludes_draft_and_archived_games_and_empty_categories(): void
    {
        $activeCategoryWithGames = Category::factory()->create([
            'slug' => 'active-cat',
            'status' => 'active',
        ]);

        $emptyCategory = Category::factory()->create([
            'slug' => 'empty-cat',
            'status' => 'active',
        ]);

        $publishedGame = Game::factory()->create([
            'slug' => 'pub-game',
            'category_id' => $activeCategoryWithGames->id,
            'status' => 'published',
        ]);

        $draftGame = Game::factory()->create([
            'slug' => 'draft-game-hidden',
            'category_id' => $activeCategoryWithGames->id,
            'status' => 'draft',
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Published game and category with published games must be present
        $this->assertStringContainsString('/games/pub-game', $content);
        $this->assertStringContainsString('/category/active-cat', $content);

        // Draft game and empty category must NOT be in sitemap
        $this->assertStringNotContainsString('/games/draft-game-hidden', $content);
        $this->assertStringNotContainsString('/category/empty-cat', $content);

        // No artificial priority or changefreq tags
        $this->assertStringNotContainsString('<priority>', $content);
        $this->assertStringNotContainsString('<changefreq>', $content);
    }

    public function test_robots_txt_returns_text_plain_with_security_disallows_and_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=utf-8');

        $content = $response->getContent();
        $this->assertStringContainsString('User-agent: *', $content);
        $this->assertStringContainsString('Disallow: /admin/', $content);
        $this->assertStringContainsString('Disallow: /dashboard', $content);
        $this->assertStringContainsString('Disallow: /login', $content);
        $this->assertStringContainsString('Disallow: /register', $content);
        $this->assertStringContainsString('Disallow: /profile', $content);
        $this->assertStringContainsString('Disallow: /games/*/*.html', $content);
        $this->assertStringContainsString('Disallow: /games/*/*.htm', $content);
        $this->assertStringContainsString('Sitemap:', $content);
    }
}
