<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CmsPageController extends Controller
{
    public function index(): View
    {
        $pages = CmsPage::query()
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('admin.cms.index', compact('pages'));
    }

    public function create(): View
    {
        return view('admin.cms.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? Str::slug($data['title']));

        CmsPage::query()->create($data);

        return redirect()->route('admin.cms.index')->with('success', 'Page created successfully.');
    }

    public function edit(CmsPage $cmsPage): View
    {
        return view('admin.cms.edit', compact('cmsPage'));
    }

    public function update(Request $request, CmsPage $cmsPage): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? Str::slug($data['title']), $cmsPage->id);

        $cmsPage->update($data);

        return redirect()->route('admin.cms.index')->with('success', 'Page updated successfully.');
    }

    public function destroy(CmsPage $cmsPage): RedirectResponse
    {
        $cmsPage->delete();

        return redirect()->route('admin.cms.index')->with('success', 'Page deleted successfully.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'is_published' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]) + [
            'is_published' => $request->boolean('is_published'),
            'sort_order' => $request->input('sort_order', 0),
        ];
    }

    protected function uniqueSlug(string $slug, ?int $exceptId = null): string
    {
        $base = Str::slug($slug) ?: 'page';
        $candidate = $base;
        $counter = 1;

        while (CmsPage::query()
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->where('slug', $candidate)
            ->exists()) {
            $candidate = $base.'-'.$counter++;
        }

        return $candidate;
    }
}
