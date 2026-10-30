<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_page_loads_with_category_games(): void
    {
        $category = Category::factory()->create(['name' => 'Racing', 'slug' => 'racing']);
        Game::factory()->count(3)->create(['category_id' => $category->id, 'status' => 'published']);

        $response = $this->get('/category/racing');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Categories/Show')
            ->has('category')
            ->where('category.name', 'Racing')
            ->has('games.data', 3)
            ->has('meta')
        );
    }

    public function test_non_existent_category_returns_404(): void
    {
        $this->get('/category/unknown-category')->assertStatus(404);
    }
}
