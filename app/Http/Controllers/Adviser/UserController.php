<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/*
 * Adviser role assignment is limited to student/officer accounts. Promotion requires an
 * officer position, demotion clears it, and advisers cannot change their own or peer roles.
 */
/**
 * Lists user information and provides adviser-only role assignment and read-only details.
 */
class UserController extends Controller
{
    /**
     * Search and paginate user accounts, including their academic profile fields.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(['student', 'officer', 'adviser'])],
        ]);

        $users = User::query()
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('student_number', 'like', '%' . $search . '%')
                        ->orWhere('institute', 'like', '%' . $search . '%')
                        ->orWhere('program', 'like', '%' . $search . '%')
                        ->orWhere('year_level', 'like', '%' . $search . '%');
                });
            })
            ->when($filters['role'] ?? null, fn ($query, string $role) => $query->where('role', $role))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('adviser.users.index', compact('users'));
    }

    /**
     * Show an account's full profile to an authorized adviser without edit controls.
     */
    public function show(User $user): View
    {
        return view('adviser.users.show', compact('user'));
    }

    /**
     * Change a non-adviser user's role and keep officer position consistent with that role.
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user) || $user->isAdviser(), 403);

        $validated = $request->validate([
            'role' => ['required', Rule::in(['student', 'officer'])],
            'officer_position' => [
                Rule::requiredIf($request->input('role') === 'officer'),
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        $user->role = $validated['role'];
        $user->officer_position = $validated['role'] === 'officer'
            ? trim($validated['officer_position'])
            : null;
        $user->save();

        return redirect()
            ->route('adviser.users.index', $request->only('search', 'role', 'page'))
            ->with('status', 'The user role was updated.');
    }
}
