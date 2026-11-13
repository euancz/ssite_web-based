# Public landing page

The landing page uses the site's sky-blue/navy palette, Poppins typography,
Pixelify Sans wordmarks, and existing SSITE mascot. Its styles are isolated in
`public/css/landing.css`. The layout and content live in the two landing Blade
templates; the Microsoft mark is a reusable four-color SVG component.

## Enable the app download

Until an app release is published, the Download app links open the mobile app
section, which explicitly shows a coming-soon message and a browser sign-in link.
The phone illustration is labeled as a preview, not a live screen.

When a release or store listing is ready, set `SSITE_APP_DOWNLOAD_URL` in the
deployment environment to its public HTTPS URL. Rebuild the Laravel config cache
with `php artisan config:cache` if the deployment uses cached configuration.
The page then shows the active download link and updates its release messaging.
Missing or invalid URLs keep the unavailable state. No APK or store listing is
bundled with this change.

## Checks

Run `php artisan test --filter=LandingPageTest` for the release states and sign-in
error rendering. Existing Microsoft authentication tests cover the sign-in flow.

The redesign was also checked in Chrome at 1440, 768, 390, and 320 pixels:
no horizontal overflow or broken images; app-section navigation, keyboard FAQ
activation, reduced motion, and rendering without JavaScript passed.

The broader authentication run currently has one existing expectation mismatch:
`test_existing_linked_account_keeps_its_role_and_profile` expects the officer
dashboard, while the current callback redirects to Home. Authentication code was
not changed for the landing-page redesign.
