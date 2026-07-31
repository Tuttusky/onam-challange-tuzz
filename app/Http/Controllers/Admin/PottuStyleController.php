<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\PottuStyle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PottuStyleController extends Controller
{
    public function index(Campaign $campaign): View
    {
        $styles = PottuStyle::query()
            ->where('campaign_id', $campaign->id)
            ->orderBy('sort_order')
            ->paginate(20);

        return view('admin.pottu.styles.index', compact('campaign', 'styles'));
    }

    public function create(Campaign $campaign): View
    {
        return view('admin.pottu.styles.form', [
            'campaign' => $campaign,
            'style' => new PottuStyle,
        ]);
    }

    public function store(Request $request, Campaign $campaign): RedirectResponse
    {
        PottuStyle::query()->create($this->validated($request, $campaign));

        return redirect()
            ->route('admin.campaigns.pottu-styles.index', $campaign)
            ->with('success', 'Pottu style added.');
    }

    public function edit(Campaign $campaign, PottuStyle $pottuStyle): View
    {
        abort_unless((int) $pottuStyle->campaign_id === (int) $campaign->id, 404);

        return view('admin.pottu.styles.form', [
            'campaign' => $campaign,
            'style' => $pottuStyle,
        ]);
    }

    public function update(Request $request, Campaign $campaign, PottuStyle $pottuStyle): RedirectResponse
    {
        abort_unless((int) $pottuStyle->campaign_id === (int) $campaign->id, 404);
        $pottuStyle->update($this->validated($request, $campaign));

        return redirect()
            ->route('admin.campaigns.pottu-styles.index', $campaign)
            ->with('success', 'Pottu style updated.');
    }

    public function destroy(Campaign $campaign, PottuStyle $pottuStyle): RedirectResponse
    {
        abort_unless((int) $pottuStyle->campaign_id === (int) $campaign->id, 404);
        $pottuStyle->delete();

        return redirect()
            ->route('admin.campaigns.pottu-styles.index', $campaign)
            ->with('success', 'Pottu style deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, Campaign $campaign): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'path' => ['required', 'string', 'max:500'],
            'type' => ['required', 'in:image,lottie'],
            'default_size' => ['nullable', 'integer', 'min:16', 'max:200'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]) + [
            'campaign_id' => $campaign->id,
            'is_active' => $request->boolean('is_active', true),
            'default_size' => (int) $request->input('default_size', 48),
            'sort_order' => (int) $request->input('sort_order', 0),
        ];
    }
}
