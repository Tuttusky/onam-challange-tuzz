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
            ->with('success', 'Girl image uploaded successfully.');
    }

    public function edit(Campaign $campaign, PottuImage $pottu_image): View
    {
        return view('admin.pottu.images.form', [
            'campaign' => $campaign,
            'image' => $pottu_image,
        ]);
    }

    public function update(Request $request, Campaign $campaign, PottuImage $pottu_image): RedirectResponse
    {
        $pottu_image->update($this->validated($request, $campaign, $pottu_image));

        return redirect()
            ->route('admin.campaigns.pottu-images.index', $campaign)
            ->with('success', 'Girl image updated successfully.');
    }

    public function destroy(Campaign $campaign, PottuImage $pottu_image): RedirectResponse
    {
        if (! empty($pottu_image->path) && str_contains($pottu_image->path, 'pottu-custom-images')) {
            $relativePath = str_replace('/storage/', '', $pottu_image->path);
            \Illuminate\Support\Facades\Storage::disk('public')->delete($relativePath);
        }

        $pottu_image->delete();

        return redirect()
            ->route('admin.campaigns.pottu-images.index', $campaign)
            ->with('success', 'Girl image deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, Campaign $campaign, ?PottuImage $image = null): array
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'image_file' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'path' => ['nullable', 'string', 'max:500'],
            'width' => ['nullable', 'integer', 'min:1', 'max:4000'],
            'height' => ['nullable', 'integer', 'min:1', 'max:4000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $imagePath = $data['path'] ?? $image?->path ?? '';

        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $storedPath = $file->store('pottu-custom-images', 'public');
            $imagePath = \Illuminate\Support\Facades\Storage::url($storedPath);

            if (empty($data['width']) || empty($data['height'])) {
                [$w, $h] = @getimagesize($file->getRealPath()) ?: [600, 900];
                $data['width'] = $data['width'] ?? $w;
                $data['height'] = $data['height'] ?? $h;
            }
        }

        if (empty($imagePath)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'image_file' => 'Please upload an image file or enter an image URL.',
            ]);
        }

        return [
            'campaign_id' => $campaign->id,
            'title' => $data['title'] ?? 'Onam Girl',
            'path' => $imagePath,
            'width' => (int) ($data['width'] ?? 600),
            'height' => (int) ($data['height'] ?? 900),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active', true),
        ];
    }
}
