<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_unreleased_app_has_an_honest_fallback_and_browser_sign_in(): void
    {
        config(['mobile.download_url' => null]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Download app')
            ->assertSee('App download coming soon')
            ->assertSee('disabled aria-describedby="app-status"', false)
            ->assertSee('Continue in your browser')
            ->assertSee(route('microsoft.login'))
            ->assertDontSee('data-app-download', false);
    }

    public function test_published_app_uses_the_configured_release_url(): void
    {
        config(['mobile.download_url' => 'https://example.com/ssite.apk']);

        $this->get('/')
            ->assertOk()
            ->assertSee('href="https://example.com/ssite.apk" data-app-download', false)
            ->assertSee('AVAILABLE TO DOWNLOAD')
            ->assertDontSee('App download coming soon')
            ->assertDontSee('when it’s ready');
    }

    public function test_invalid_download_urls_keep_the_release_unavailable(): void
    {
        foreach (['javascript:alert(1)', 'not-a-url', '//example.com/app', 'ftp://example.com/app.apk'] as $url) {
            config(['mobile.download_url' => $url]);

            $this->get('/')
                ->assertOk()
                ->assertSee('App download coming soon')
                ->assertDontSee('data-app-download', false);
        }
    }

    public function test_authentication_errors_remain_visible_and_escaped(): void
    {
        $this->withSession(['error' => 'Sign-in failed <script>alert(1)</script>'])
            ->get('/')
            ->assertOk()
            ->assertSee('role="alert"', false)
            ->assertSee('Sign-in failed <script>alert(1)</script>')
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}
