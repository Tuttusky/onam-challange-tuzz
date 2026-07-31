<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FriendAvatar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FriendAvatarController extends Controller
{
    public function index(): View
    {
        $avatars = FriendAvatar::query()->orderBy('sort_order')->paginate(20);

        return view('admin.friend-avatars.index', compact('avatars'));
    }

    public function create(): View
    {
        return view('admin.friend-avatars.form', ['avatar' => new FriendAvatar]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        FriendAvatar::query()->create($data);

        return redirect()->route('admin.friend-avatars.index')->with('success', 'Avatar created.');
    }

    public function edit(FriendAvatar $friendAvatar): View
    {
        return view('admin.friend-avatars.form', ['avatar' => $friendAvatar]);
    }

    public function update(Request $request, FriendAvatar $friendAvatar): RedirectResponse
    {
        $friendAvatar->update($this->validated($request));

        return redirect()->route('admin.friend-avatars.index')->with('success', 'Avatar updated.');
    }

    public function destroy(FriendAvatar $friendAvatar): RedirectResponse
    {
        $friendAvatar->delete();

        return redirect()->route('admin.friend-avatars.index')->with('success', 'Avatar deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash'],
            'path' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]) + [
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) $request->input('sort_order', 0),
        ];
    }
}
