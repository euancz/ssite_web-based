<?php

namespace App\Http\Controllers\Adviser;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOfficerTermRequest;
use App\Http\Requests\UpdateOfficerTermRequest;
use App\Models\OfficerTerm;
use App\Models\User;
use App\Support\AcademicYear;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Adviser-only management of yearly officer snapshots; route middleware also blocks other roles. */
class OfficerTermController extends Controller
{
    /** List one year and return advisers to the filtered index after writes. */
    public function index(Request $request): View
    {
        abort_unless($request->user()->isAdviser(), 403);
        $years = AcademicYear::options();
        $year = $request->query('academic_year', AcademicYear::current());
        if (! AcademicYear::isValid($year)) $year = AcademicYear::current();
        $officers = OfficerTerm::forYear($year)->inDisplayOrder()->get();

        return view('adviser.officers.index', compact('years', 'year', 'officers'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->isAdviser(), 403);
        $officer = new OfficerTerm(['academic_year' => $request->query('academic_year', AcademicYear::current())]);
        return view('adviser.officers.create', $this->formData($officer));
    }

    public function store(StoreOfficerTermRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $term = new OfficerTerm(collect($data)->except(['user_id', 'photo'])->all());
        $term->user_id = $data['user_id'] ?? null;
        $term->photo = $request->hasFile('photo') ? $this->storePhoto($request) : $this->linkedPhoto($term->user_id);
        $term->save();

        return redirect()->route('adviser.officers.index', ['academic_year' => $term->academic_year])->with('status', 'Officer added.');
    }

    public function edit(Request $request, OfficerTerm $officer): View
    {
        abort_unless($request->user()->isAdviser(), 403);
        return view('adviser.officers.edit', $this->formData($officer));
    }

    public function update(UpdateOfficerTermRequest $request, OfficerTerm $officer): RedirectResponse
    {
        $oldPhoto = $officer->photo;
        $oldUserId = $officer->user_id;
        $data = $request->validated();
        $officer->fill(collect($data)->except(['user_id', 'photo'])->all());
        $officer->user_id = $data['user_id'] ?? null;
        if ($request->hasFile('photo')) {
            $officer->photo = $this->storePhoto($request);
        } elseif ($officer->user_id && ($officer->user_id !== $oldUserId || !$officer->photo)) {
            $officer->photo = $this->linkedPhoto($officer->user_id);
        }
        $officer->save();
        if ($oldPhoto !== $officer->photo) $this->deleteManagedPhoto($oldPhoto);

        return redirect()->route('adviser.officers.index', ['academic_year' => $officer->academic_year])->with('status', 'Officer updated.');
    }

    public function destroy(Request $request, OfficerTerm $officer): RedirectResponse
    {
        abort_unless($request->user()->isAdviser(), 403);
        $year = $officer->academic_year;
        $photo = $officer->photo;
        $officer->delete();
        $this->deleteManagedPhoto($photo);

        return redirect()->route('adviser.officers.index', ['academic_year' => $year])->with('status', 'Officer deleted.');
    }

    public function copyPrevious(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdviser(), 403);
        $data = $request->validate(['academic_year' => ['required', 'string', function ($attribute, $value, $fail): void {
            if (! AcademicYear::isValid($value)) $fail('Choose a valid consecutive academic year.');
        }]]);
        [$start] = array_map('intval', explode('-', $data['academic_year']));
        $previous = ($start - 1) . '-' . $start;
        foreach (OfficerTerm::forYear($previous)->get() as $source) {
            $exists = OfficerTerm::forYear($data['academic_year'])->where(function ($query) use ($source): void {
                if ($source->user_id !== null) $query->where('user_id', $source->user_id);
                else $query->whereNull('user_id')->where('name', $source->name)->where('position', $source->position);
            })->exists();
            if (!$exists) {
                $copy = new OfficerTerm($source->only(['name', 'position', 'photo', 'sort_order']));
                $copy->academic_year = $data['academic_year'];
                $copy->user_id = $source->user_id;
                $copy->save();
            }
        }

        return redirect()->route('adviser.officers.index', ['academic_year' => $data['academic_year']])->with('status', 'Previous year officers copied where no matching row existed.');
    }

    private function formData(OfficerTerm $officer): array
    {
        return [
            'officer' => $officer,
            'years' => AcademicYear::options(),
            'positions' => config('school.officer_positions', []),
            'users' => User::query()->whereIn('role', ['student', 'officer'])->orderBy('name')->get(['user_id', 'name', 'profile_picture']),
        ];
    }

    private function storePhoto(Request $request): string
    {
        $file = $request->file('photo');
        $filename = Str::uuid() . '.' . strtolower($file->extension());
        return $file->storeAs('officer-photos', $filename, 'public');
    }

    private function linkedPhoto(?int $userId): ?string
    {
        return $userId ? User::where('user_id', $userId)->value('profile_picture') : null;
    }

    /** SECURITY: Delete only generated local term uploads, never profile files or external URLs. */
    private function deleteManagedPhoto(?string $photo): void
    {
        if ($photo && str_starts_with($photo, 'officer-photos/') && !preg_match('/^https?:\/\//i', $photo)) {
            Storage::disk('public')->delete($photo);
        }
    }
}
