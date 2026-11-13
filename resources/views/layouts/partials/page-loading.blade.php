@php
    // Only describe page routes: downloads, JSON endpoints and mutations must not
    // leave an overlay on a document that the browser never navigates away from.
    $loadingRoutes = [];
    foreach (\Illuminate\Support\Facades\Route::getRoutes() as $loadingRoute) {
        if (! in_array('GET', $loadingRoute->methods(), true)) continue;
        $name = $loadingRoute->getName() ?? '';
        $kind = match (true) {
            in_array($name, ['home', 'landing']) => 'home',
            $name === 'about' => 'about',
            $name === 'discover' => 'discover',
            str_ends_with($name, '.dashboard') => 'dashboard',
            str_starts_with($name, 'profile.') => 'profile',
            $name === 'notifications.index' => 'notifications',
            $name === 'search.index' => 'search',
            str_ends_with($name, '.create'), str_ends_with($name, '.edit') => 'form',
            str_starts_with($name, 'adviser.users.') => str_ends_with($name, '.show') ? 'profile' : 'table',
            str_starts_with($name, 'adviser.'),
            str_starts_with($name, 'workspace.drafts.'),
            str_starts_with($name, 'workspace.content-') => 'table',
            preg_match('/(?:articles|activities|achievements|documents|liquidations)\.show$/', $name) === 1 => 'detail',
            $name === 'transparency',
            preg_match('/(?:articles|activities|achievements|documents|liquidations)\.index$/', $name) === 1 => 'feed',
            default => null,
        };
        if ($kind === null) continue;
        $loadingRoutes[] = [
            'path' => parse_url(url($loadingRoute->uri()), PHP_URL_PATH) ?: '/',
            'kind' => $kind,
            'workspace' => (bool) ($workspaceRole && \Illuminate\Support\Str::is($workspaceRoutePatterns, $name)),
        ];
    }
@endphp
<script type="application/json" id="page-loading-routes">{!! json_encode($loadingRoutes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
<script src="{{ asset('js/page-loading.js') }}" defer></script>
