<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\Campaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BadgeController extends Controller
{
    public function index(Request $request): View
    {
        $badges = Badge::query()
            ->with('campaign')
            ->when($request->filled('campaign_id'), fn ($q) => $q->where('campaign_id', $request->campaign_id))
            ->orderBy('sort_order')
            ->paginate(20)
            ->withQueryString();

        $campaigns = Campaign::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.badges.index', compact('badges', 'campaigns'));
    }

    public function create(): View
    {
        $campaigns = Campaign::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.badges.create', compact('campaigns'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['name']);

        Badge::query()->create($data);

        return redirect()->route('admin.badges.index')->with('success', 'Badge created successfully.');
    }

    public function edit(Badge $badge): View
    {
        $campaigns = Campaign::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.badges.edit', compact('badge', 'campaigns'));
    }

    public function update(Request $request, Badge $badge): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['name']);

        $badge->update($data);

        return redirect()->route('admin.badges.index')->with('success', 'Badge updated successfully.');
    }

    public function destroy(Badge $badge): RedirectResponse
    {
        $badge->delete();

        return redirect()->route('admin.badges.index')->with('success', 'Badge deleted successfully.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'campaign_id' => ['nullable', 'exists:campaigns,id'],
            'name' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'string', 'max:255'],
            'min_match_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'max_match_percent' => ['required', 'integer', 'min:0', 'max:100', 'gte:min_match_percent'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]) + [
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $request->input('sort_order', 0),
        ];
    }
}
