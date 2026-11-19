<?php

namespace App\Http\Controllers\Adviser;

use App\Support\ContentPagination;
use App\Support\SearchText;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use App\Support\SearchSuggestions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/*
 * Adviser and admin assignment paths are separated; configured positions are never free text.
 */
/**
 * Lists user information and provides actor-scoped role assignment and read-only details.
 */
class UserController extends Controller
{
    /**
     * Search and paginate user accounts by name, email, and role.
     */
    public function index(Request $request): View|JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor?->canAssignOfficers() || $actor?->canAssignAdvisers(), 403);
        $targetRoles = $actor->canAssignAdvisers()
            ? [User::ROLE_STUDENT, User::ROLE_OFFICER, User::ROLE_ADVISER]
            : [User::ROLE_STUDENT, User::ROLE_OFFICER];
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in($targetRoles)],
        ]);

        $query = User::query()->whereIn('role', $targetRoles)
            ->when(trim($filters['search'] ?? '') !== '', function ($query) use ($filters): void {
                $search = trim($filters['search']);
                $query->where(function ($query) use ($search): void {
                    SearchText::contains($query, 'name', $search);
                    SearchText::contains($query, 'email', $search, 'or');
                });
            })
            ->when($filters['role'] ?? null, fn ($query, string $role) => $query->where('role', $role))
            ->orderBy('name')->orderBy('id');
        if (SearchSuggestions::requested($request)) {
            return SearchSuggestions::fromQuery($request, $query, 'name');
        }

        $users = ContentPagination::paginate($query, 15)->appends($filters);

        $positions = array_keys(config('school.positions', []));

        return view('adviser.users.index', compact('users', 'targetRoles', 'positions'));
    }

    /**
     * Show a target account's profile to a manager permitted to change its role.
     */
    public function show(User $user): View
    {
        $this->authorize('changeRole', $user);
        return view('adviser.users.show', compact('user'));
    }

    /**
     * Change an assignable user's role and keep officer position consistent with that role.
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        Gate::forUser($actor)->authorize('changeRole', $user);
        $allowedRoles = $actor->canAssignOfficers()
            ? [User::ROLE_STUDENT, User::ROLE_OFFICER]
            : match ($user->role) {
                User::ROLE_ADVISER => [User::ROLE_STUDENT],
                User::ROLE_STUDENT, User::ROLE_OFFICER => [User::ROLE_ADVISER],
                default => [],
            };
        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(array_intersect($actor->assignableRoles(), $allowedRoles))],
            'officer_position' => [
                Rule::requiredIf($request->input('role') === User::ROLE_OFFICER),
                'nullable', 'string', 'max:100',
                Rule::in(array_keys(config('school.positions', []))),
            ],
        ]);
        $newRole = $validated['role'];
        $newPosition = $newRole === User::ROLE_OFFICER ? $validated['officer_position'] : null;

        DB::transaction(function () use ($actor, $user, $newRole, $newPosition): void {
            // SECURITY: Lock assignment rows together so concurrent requests cannot create two Presidents.
            $lockedUsers = User::query()->orderBy('id')->lockForUpdate()->get();
            $lockedActor = $lockedUsers->firstWhere('id', $actor->id);
            $target = $lockedUsers->firstWhere('id', $user->id);
            abort_unless($lockedActor && $target, 404);
            Gate::forUser($lockedActor)->authorize('changeRole', $target);

            $allowedRoles = $lockedActor->canAssignOfficers()
                ? [User::ROLE_STUDENT, User::ROLE_OFFICER]
                : match ($target->role) {
                    User::ROLE_ADVISER => [User::ROLE_STUDENT],
                    User::ROLE_STUDENT, User::ROLE_OFFICER => [User::ROLE_ADVISER],
                    default => [],
                };
            abort_unless(in_array($newRole, array_intersect($lockedActor->assignableRoles(), $allowedRoles), true), 422);

            $oldRole = $target->role;
            $oldPosition = $target->officer_position;
            $singleHolderPositions = config('school.single_holder_positions', []);
            if ($newRole === User::ROLE_OFFICER && in_array($newPosition, $singleHolderPositions, true)) {
                $alreadyAssigned = $lockedUsers->contains(function (User $candidate) use ($target, $newPosition): bool {
                    return (int) $candidate->id !== (int) $target->id
                        && $candidate->isOfficer()
                        && $candidate->position() === $newPosition;
                });
                if ($alreadyAssigned) {
                    throw ValidationException::withMessages([
                        'officer_position' => "A {$newPosition} is already assigned. Reassign the current holder first.",
                    ]);
                }
            }

            $target->role = $newRole;
            $target->officer_position = $newRole === User::ROLE_OFFICER ? $newPosition : null;
            $target->save();

            if (Schema::hasTable('role_changes')) {
                DB::table('role_changes')->insert([
                    'user_id' => $target->id,
                    'changed_by_user_id' => $lockedActor->id,
                    'old_role' => $oldRole,
                    'new_role' => $newRole,
                    'old_officer_position' => $oldPosition,
                    'new_officer_position' => $newPosition,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return redirect()
            ->route('adviser.users.index', $request->only('search', 'role', 'page'))
            ->with('status', 'The user role was updated.');
    }
}
