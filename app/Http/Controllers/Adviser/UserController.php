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
use Illuminate\Validation\ValidationException;

/*
 * Adviser and superadmin assignment paths are separated; configured positions are never free text.
 */
/**
 * Lists user information and provides actor-scoped role assignment and read-only details.
 */
class UserController extends Controller
{
    /**
     * Search and paginate user accounts, including their academic profile fields.
     */
    public function index(Request $request): View
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

        $users = User::query()->whereIn('role', $targetRoles)
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

        $removedPhoto = null;
        DB::transaction(function () use ($actor, $user, $newRole, $newPosition, &$removedPhoto): void {
            // SECURITY: Lock assignment rows together so concurrent requests cannot create two Presidents.
            $lockedUsers = User::query()->orderBy('user_id')->lockForUpdate()->get();
            $lockedActor = $lockedUsers->firstWhere('user_id', $actor->user_id);
            $target = $lockedUsers->firstWhere('user_id', $user->user_id);
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
                    return (int) $candidate->user_id !== (int) $target->user_id
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

            $currentYear = AcademicYear::current();
            if ($newRole === User::ROLE_OFFICER) {
                $term = OfficerTerm::updateOrCreate(
                    ['user_id' => $target->user_id, 'academic_year' => $currentYear],
                    ['name' => $target->name, 'position' => $newPosition, 'photo' => $target->profile_picture]
                );
                $term->user_id = $target->user_id;
                $term->save();
            } elseif ($oldRole === User::ROLE_OFFICER) {
                $term = OfficerTerm::forYear($currentYear)->where('user_id', $target->user_id)->first();
                if ($term) {
                    $removedPhoto = $term->photo;
                    $term->delete();
                }
            }

            if (Schema::hasTable('role_changes')) {
                DB::table('role_changes')->insert([
                    'user_id' => $target->user_id,
                    'changed_by_user_id' => $lockedActor->user_id,
                    'old_role' => $oldRole,
                    'new_role' => $newRole,
                    'old_officer_position' => $oldPosition,
                    'new_officer_position' => $newPosition,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        if ($removedPhoto && str_starts_with($removedPhoto, 'officer-photos/') && ! preg_match('/^https?:\/\//i', $removedPhoto)) {
            Storage::disk('public')->delete($removedPhoto);
        }

        return redirect()
            ->route('adviser.users.index', $request->only('search', 'role', 'page'))
            ->with('status', 'The user role was updated.');
    }
}
