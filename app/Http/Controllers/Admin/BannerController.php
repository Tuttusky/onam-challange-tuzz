<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function index(): View
    {
        $banners = Banner::query()
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('admin.banners.index', compact('banners'));
    }

    public function create(): View
    {
        return view('admin.banners.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Banner::query()->create($this->validated($request));

        return redirect()->route('admin.banners.index')->with('success', 'Banner created successfully.');
    }

    public function edit(Banner $banner): View
    {
        return view('admin.banners.edit', compact('banner'));
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $banner->update($this->validated($request));

        return redirect()->route('admin.banners.index')->with('success', 'Banner updated successfully.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        $banner->delete();

        return redirect()->route('admin.banners.index')->with('success', 'Banner deleted successfully.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:header,footer,popup,festival,advertisement'],
            'image' => ['nullable', 'string', 'max:255'],
            'link' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]) + [
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $request->input('sort_order', 0),
        ];
    }
}
