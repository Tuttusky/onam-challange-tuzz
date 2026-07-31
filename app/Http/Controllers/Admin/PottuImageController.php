<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\PottuImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PottuImageController extends Controller
{
    public function index(Campaign $campaign): View
    {
        $images = PottuImage::query()
            ->where('campaign_id', $campaign->id)
            ->orderBy('sort_order')
            ->paginate(20);

        return view('admin.pottu.images.index', compact('campaign', 'images'));
    }

    public function create(Campaign $campaign): View
    {
        return view('admin.pottu.images.form', [
            'campaign' => $campaign,
            'image' => new PottuImage,
        ]);
    }

    public function store(Request $request, Campaign $campaign): RedirectResponse
    {
        PottuImage::query()->create($this->validated($request, $campaign));

        return redirect()
            ->route('admin.campaigns.pottu-images.index', $campaign)
            ->with('success', 'Girl image added.');
    }

    public function edit(Campaign $campaign, PottuImage $pottuImage): View
    {
        abort_unless((int) $pottuImage->campaign_id === (int) $campaign->id, 404);

        return view('admin.pottu.images.form', [
            'campaign' => $campaign,
            'image' => $pottuImage,
        ]);
    }

    public function update(Request $request, Campaign $campaign, PottuImage $pottuImage): RedirectResponse
    {
        abort_unless((int) $pottuImage->campaign_id === (int) $campaign->id, 404);
        $pottuImage->update($this->validated($request, $campaign));

        return redirect()
            ->route('admin.campaigns.pottu-images.index', $campaign)
            ->with('success', 'Girl image updated.');
    }

    public function destroy(Campaign $campaign, PottuImage $pottuImage): RedirectResponse
    {
        abort_unless((int) $pottuImage->campaign_id === (int) $campaign->id, 404);
        $pottuImage->delete();

        return redirect()
            ->route('admin.campaigns.pottu-images.index', $campaign)
            ->with('success', 'Girl image deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, Campaign $campaign): array
    {
        return $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'path' => ['required', 'string', 'max:500'],
            'width' => ['nullable', 'integer', 'min:1', 'max:4000'],
            'height' => ['nullable', 'integer', 'min:1', 'max:4000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]) + [
            'campaign_id' => $campaign->id,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) $request->input('sort_order', 0),
        ];
    }
}
