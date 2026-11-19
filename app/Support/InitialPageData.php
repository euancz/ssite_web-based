<?php

namespace App\Support;

use Illuminate\Http\Request;

final class InitialPageData
{
    public static function pending(Request $request): bool
    {
        // Only browser documents get the shell. Fragment requests, other HTTP clients,
        // and the regular-page fallback receive the complete server-rendered view.
        return $request->isMethod('GET')
            && $request->header('Sec-Fetch-Dest') === 'document'
            && $request->header('X-SSITE-Page-Data') !== '1'
            && ! $request->query->has('_full')
            && ! $request->session()->hasAny(['status', 'error', 'errors', 'success']);
    }
}
