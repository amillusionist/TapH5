<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_loads_successfully_with_data(): void
    {
        $category = Category::factory()->create(['name' => 'Arcade', 'slug' => 'arcade', 'status' => 'active']);
        Game::factory()->count(4)->create([
            'category_id' => $category->id,
            'status' => 'published',
            'is_featured' => true,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('heroGames')
            ->has('featuredGames')
            ->has('categories')
            ->has('meta')
        );
    }

    public function test_homepage_handles_empty_database_gracefully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('heroGames', [])
            ->where('featuredGames', [])
            ->where('categories', [])
        );
    }
}
