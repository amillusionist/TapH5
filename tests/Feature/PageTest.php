<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PageTest extends TestCase
{
    use RefreshDatabase;

    public function test_informational_and_legal_pages_load(): void
    {
        $pages = [
            '/about' => 'Pages/About',
            '/contact' => 'Pages/Contact',
            '/privacy' => 'Pages/Privacy',
            '/terms' => 'Pages/Terms',
            '/cookie-policy' => 'Pages/CookiePolicy',
            '/data-policy' => 'Pages/DataPolicy',
        ];

        foreach ($pages as $url => $component) {
            $response = $this->get($url);
            $response->assertStatus(200);
            $response->assertInertia(fn (Assert $page) => $page
                ->component($component)
                ->has('meta')
            );
        }
    }

    public function test_custom_404_error_page_renders_with_links(): void
    {
        $response = $this->get('/non-existent-random-route-404');
        $response->assertStatus(404);
        $response->assertSee('Game Over — Page Not Found');
        $response->assertSee('Explore Games Catalog');
        $response->assertSee('Return to Homepage');
    }
}
