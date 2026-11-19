<?php

namespace App\Http\Controllers;

use App\Support\ContentPagination;
use App\Support\SearchText;

use App\Http\Requests\SponsorRequest;
use App\Models\Sponsor;
use Illuminate\Http\RedirectResponse;
use App\Support\SearchSuggestions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SponsorController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'visibility' => ['nullable', 'in:visible,hidden'],
            'sort' => ['nullable', 'in:display,name,recent'],
        ]);
        $filters = array_replace(['q' => '', 'visibility' => '', 'sort' => 'display'], array_filter($filters, fn ($value) => $value !== null));
        $filters['q'] = trim($filters['q']);
        $query = Sponsor::query();
        if ($filters['q'] !== '') {
            $query->where(function ($query) use ($filters) {
                SearchText::contains($query, 'name', $filters['q']);
                SearchText::contains($query, 'description', $filters['q'], 'or');
            });
        }
        if ($filters['visibility'] !== '') $query->where('is_active', $filters['visibility'] === 'visible');
        match ($filters['sort']) {
            'name' => $query->orderBy('name')->orderBy('id'),
            'recent' => $query->orderByDesc('created_at')->orderByDesc('id'),
            default => $query->inDisplayOrder(),
        };
        if (SearchSuggestions::requested($request)) {
            return SearchSuggestions::fromQuery($request, $query, 'name');
        }

        return view('sponsors.index', ['sponsors' => ContentPagination::paginate($query, 20)->appends($filters), 'filters' => $filters]);
    }

    public function create(): View
    {
        return view('sponsors.form', ['sponsor' => new Sponsor(['is_active' => true, 'sort_order' => 0])]);
    }

    public function edit(Sponsor $sponsor): View
    {
        return view('sponsors.form', compact('sponsor'));
    }

    public function store(SponsorRequest $request): RedirectResponse
    {
        return $this->save($request, new Sponsor);
    }

    public function update(SponsorRequest $request, Sponsor $sponsor): RedirectResponse
    {
        return $this->save($request, $sponsor);
    }

    private function save(SponsorRequest $request, Sponsor $sponsor): RedirectResponse
    {
        $oldLogo = $sponsor->logo_path;
        $sponsor->fill($request->safe()->except(['logo', 'remove_logo']));
        if ($request->boolean('remove_logo')) $sponsor->logo_path = null;
        $newLogo = null;
        if ($request->hasFile('logo')) {
            $newLogo = $request->file('logo')->store('sponsor-logos', 'public');
            abort_unless($newLogo, 503, 'Logo upload failed. Please try again.');
            $sponsor->logo_path = $newLogo;
        }
        try {
            $sponsor->save();
        } catch (\Throwable $exception) {
            $this->deleteLogo($newLogo);
            throw $exception;
        }
        if ($oldLogo !== $sponsor->logo_path) $this->deleteLogo($oldLogo);

        return redirect()->route('workspace.sponsors.index')->with('status', 'Sponsor saved.');
    }

    public function destroy(Sponsor $sponsor): RedirectResponse
    {
        $logo = $sponsor->logo_path;
        $sponsor->delete();
        $this->deleteLogo($logo);

        return redirect()->route('workspace.sponsors.index')->with('status', 'Sponsor deleted.');
    }

    private function deleteLogo(?string $path): void
    {
        if ($path && preg_match('#\Asponsor-logos/[A-Za-z0-9]{40}\.(jpg|jpeg|png|webp)\z#D', $path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
