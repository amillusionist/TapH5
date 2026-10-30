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
}
