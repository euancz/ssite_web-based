<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Support\MobileAccountSession;
use App\Support\MobileWorkspace;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SessionController extends Controller
{
    public function account(Request $request)
    {
        $validated = $request->validate(['destination' => ['required', Rule::in(array_keys(MobileAccountSession::DESTINATIONS))]]);

        return response()->json(['url' => MobileAccountSession::issue(
            $request->attributes->get('mobile_user'),
            $request->attributes->get('mobile_token_id'),
            $validated['destination'],
        )])->header('Cache-Control', 'no-store, private');
    }

    public function workspace(Request $request)
    {
        $url = MobileWorkspace::issue(
            $request->attributes->get('mobile_user'),
            $request->attributes->get('mobile_token_id'),
        );

        return response()->json(['url' => $url])->header('Cache-Control', 'no-store, private');
    }
}
