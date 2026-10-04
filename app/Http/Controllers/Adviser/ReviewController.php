<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/*
 * Intended post flow: officers submit pending posts; advisers approve or reject with a reason;
 * student-facing queries should expose approved posts only. Post persistence is not implemented yet.
 */
/**
 * Prepares the adviser review page and validates its status filter.
 */
class ReviewController extends Controller
{
    /**
     * Render the requested review tab; results remain empty until post models and tables exist.
     */
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
