<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class CreatePostController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()->canPost(), 403);

        $modules = collect(config('workspace.modules'))
            ->filter(fn ($module, $key) => $request->user()->canPostIn($key));

        return view('workspace.create', compact('modules'));
    }
}
