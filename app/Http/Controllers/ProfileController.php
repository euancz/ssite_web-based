<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProfileRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use App\Models\User;

class ProfileController extends Controller
{
    public function create(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->hasCompletedProfile()) {
            return redirect()->route('home');
        }

        return view('profile.complete', $this->formData($user));
    }

    public function store(StoreProfileRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasCompletedProfile()) {
            return redirect()->route('home');
        }

        $data = $request->validated();
        $data['contact_number'] = $this->normalizePhoneNumber($data['contact_number']);

        $user->fill($data);
        $user->profile_completed_at = now();
        $user->save();

        return redirect()->intended(route($user->dashboardRoute()))
            ->with('status', 'Your profile has been completed.');
    }

    public function edit(): View
    {
        return view('profile.edit', $this->formData(auth()->user()));
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $data['contact_number'] = $this->normalizePhoneNumber($data['contact_number']);

        $user->fill($data);
        $user->save();

        return redirect()->route('profile.edit')
            ->with('status', 'Your profile has been updated.');
    }

    private function normalizePhoneNumber(string $number): string
    {
        return str_starts_with($number, '09')
            ? '+63' . substr($number, 1)
            : $number;
    }

    private function formData(User $user): array
    {
        $programsByInstitute = config('school.programs_by_institute', []);
        $programOptions = $programsByInstitute !== []
            ? collect($programsByInstitute)->flatten()->unique()->values()->all()
            : config('school.programs', []);

        return [
            'user' => $user,
            'institutes' => config('school.institutes', []),
            'programs' => $programOptions,
            'programsByInstitute' => $programsByInstitute,
            'yearLevels' => config('school.year_levels', []),
            'genders' => config('school.genders', []),
        ];
    }
}
