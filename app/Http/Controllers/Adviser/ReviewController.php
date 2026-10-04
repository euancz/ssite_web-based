<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
        ]);

        // TODO: Query the selected status and latest pending posts after post storage is implemented.
        return view('adviser.reviews.index', [
            'posts' => collect(),
            'activeStatus' => $filters['status'] ?? 'pending',
        ]);
    }
}
