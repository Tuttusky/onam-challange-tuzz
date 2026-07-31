<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignTheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(Request $request): View
    {
        $campaigns = Campaign::query()
            ->with('theme')
            ->withCount(['questions', 'playerSessions'])
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.campaigns.index', compact('campaigns'));
    }

    public function create(): View
    {
        $themes = CampaignTheme::query()->orderBy('name')->get();

        return view('admin.campaigns.create', compact('themes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? Str::slug($data['name']));

        Campaign::query()->create($data);

        return redirect()
            ->route('admin.campaigns.index')
            ->with('success', 'Campaign created successfully.');
    }

    public function edit(Campaign $campaign): View
    {
        $themes = CampaignTheme::query()->orderBy('name')->get();

        return view('admin.campaigns.edit', compact('campaign', 'themes'));
    }

    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? Str::slug($data['name']), $campaign->id);

        $campaign->update($data);

        return redirect()
            ->route('admin.campaigns.index')
            ->with('success', 'Campaign updated successfully.');
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $campaign->delete();

        return redirect()
            ->route('admin.campaigns.index')
            ->with('success', 'Campaign deleted successfully.');
    }

    public function clone(Campaign $campaign): RedirectResponse
    {
        $clone = $campaign->replicate();
        $clone->name = $campaign->name.' (Copy)';
        $clone->slug = $this->uniqueSlug(Str::slug($clone->name));
        $clone->status = 'draft';
        $clone->save();

        foreach ($campaign->questions()->with('options')->get() as $question) {
            $newQuestion = $question->replicate();
            $newQuestion->campaign_id = $clone->id;
            $newQuestion->save();

            foreach ($question->options as $option) {
                $newOption = $option->replicate();
                $newOption->question_id = $newQuestion->id;
                $newOption->save();
            }
        }

        return redirect()
            ->route('admin.campaigns.edit', $clone)
            ->with('success', 'Campaign cloned successfully.');
    }

    public function toggleStatus(Campaign $campaign): RedirectResponse
    {
        $campaign->update([
            'status' => $campaign->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', 'Campaign status updated.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:dare_challenge,sundarikk_pottu,quiz,poll,survey'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive,draft'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'max_questions' => ['required', 'integer', 'min:1', 'max:100'],
            'max_friends' => ['required', 'integer', 'min:1', 'max:500'],
            'share_message' => ['nullable', 'string'],
            'default_challenge_title' => ['nullable', 'string', 'max:255'],
            'campaign_theme_id' => ['nullable', 'exists:campaign_themes,id'],
            'is_featured' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]) + [
            'is_featured' => $request->boolean('is_featured'),
            'sort_order' => $request->input('sort_order', 0),
        ];
    }

    protected function uniqueSlug(string $slug, ?int $exceptId = null): string
    {
        $base = Str::slug($slug) ?: 'campaign';
        $candidate = $base;
        $counter = 1;

        while (Campaign::query()
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->where('slug', $candidate)
            ->exists()) {
            $candidate = $base.'-'.$counter++;
        }

        return $candidate;
    }
}
