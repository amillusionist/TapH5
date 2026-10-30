<?php

namespace App\Http\Controllers;

use App\Services\SeoService;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function __construct(
        protected SeoService $seoService
    ) {}

    public function about(): Response
    {
        return Inertia::render('Pages/About', [
            'meta' => $this->seoService->forPage(
                'About Us',
                'Learn more about TapH5 Games, our mission to provide high performance, accessible web gaming worldwide.',
                route('pages.about')
            ),
        ]);
    }

    public function contact(): Response
    {
        return Inertia::render('Pages/Contact', [
            'meta' => $this->seoService->forPage(
                'Contact Us',
                'Get in touch with the TapH5 Games support and developer relations team.',
                route('pages.contact')
            ),
        ]);
    }

    public function privacy(): Response
    {
        return Inertia::render('Pages/Privacy', [
            'meta' => $this->seoService->forPage(
                'Privacy Policy',
                'Read the TapH5 Games privacy policy, data practices, and user privacy protection commitments.',
                route('pages.privacy')
            ),
        ]);
    }

    public function terms(): Response
    {
        return Inertia::render('Pages/Terms', [
            'meta' => $this->seoService->forPage(
                'Terms and Conditions',
                'TapH5 Games website terms of use and player conditions.',
                route('pages.terms')
            ),
        ]);
    }

    public function cookiePolicy(): Response
    {
        return Inertia::render('Pages/CookiePolicy', [
            'meta' => $this->seoService->forPage(
                'Cookie Policy',
                'Information about cookies and local browser storage used on TapH5 Games.',
                route('pages.cookie-policy')
            ),
        ]);
    }

    public function dataPolicy(): Response
    {
        return Inertia::render('Pages/DataPolicy', [
            'meta' => $this->seoService->forPage(
                'Data Policy',
                'TapH5 Games compliance, data handling, and player data policies.',
                route('pages.data-policy')
            ),
        ]);
    }
}
