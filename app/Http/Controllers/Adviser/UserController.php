<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Models\OfficerTerm;
use App\Models\User;
use App\Support\AcademicYear;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
            'role' => ['nullable', Rule::in(User::ROLES)],
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
        $actor = $request->user();
        Gate::forUser($actor)->authorize('changeRole', $user);

        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in($actor->assignableRoles())],
            'officer_position' => [
                Rule::requiredIf($request->input('role') === User::ROLE_OFFICER),
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        $oldRole = $user->role;
        $oldPosition = $user->officer_position;
        $newRole = $validated['role'];
        $newPosition = $newRole === User::ROLE_OFFICER ? trim((string) ($validated['officer_position'] ?? '')) : null;

        DB::transaction(function () use ($actor, $user, $newRole, $newPosition, $oldRole, $oldPosition) {
            $user->role = $newRole;
            $user->officer_position = $newRole === User::ROLE_OFFICER ? $newPosition : null;
            $user->save();

            $currentYear = AcademicYear::current();
            if ($newRole === User::ROLE_OFFICER) {
                $term = OfficerTerm::updateOrCreate(
                    ['user_id' => $user->user_id, 'academic_year' => $currentYear],
                    ['name' => $user->name, 'position' => $newPosition, 'photo' => $user->profile_picture]
                );
                $term->user_id = $user->user_id;
                $term->save();
            } elseif ($oldRole === User::ROLE_OFFICER) {
                $term = OfficerTerm::forYear($currentYear)->where('user_id', $user->user_id)->first();
                if ($term) {
                    if ($term->photo && str_starts_with($term->photo, 'officer-photos/') && ! preg_match('/^https?:\/\//i', $term->photo)) {
                        Storage::disk('public')->delete($term->photo);
                    }
                    $term->delete();
                }
            }

            if (Schema::hasTable('role_changes')) {
                try {
                    DB::table('role_changes')->insert([
                        'user_id' => $user->user_id,
                        'changed_by_user_id' => $actor->user_id,
                        'old_role' => $oldRole,
                        'new_role' => $newRole,
                        'old_officer_position' => $oldPosition,
                        'new_officer_position' => $newPosition,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (\Throwable $e) {
                    // Skip logging when the table schema differs from the expected snapshot columns.
                }
            }
        });

        return redirect()
            ->route('adviser.users.index', $request->only('search', 'role', 'page'))
            ->with('status', 'The user role was updated.');
    }
}
