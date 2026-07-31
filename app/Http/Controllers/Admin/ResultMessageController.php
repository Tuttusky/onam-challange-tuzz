<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\ResultMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultMessageController extends Controller
{
    public function index(Request $request): View
    {
        $messages = ResultMessage::query()
            ->with('campaign')
            ->when($request->filled('campaign_id'), fn ($q) => $q->where('campaign_id', $request->campaign_id))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $campaigns = Campaign::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.result-messages.index', compact('messages', 'campaigns'));
    }

    public function create(): View
    {
        $campaigns = Campaign::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.result-messages.create', compact('campaigns'));
    }

    public function store(Request $request): RedirectResponse
    {
        ResultMessage::query()->create($this->validated($request));

        return redirect()->route('admin.result-messages.index')->with('success', 'Result message created successfully.');
    }

    public function edit(ResultMessage $resultMessage): View
    {
        $campaigns = Campaign::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.result-messages.edit', compact('resultMessage', 'campaigns'));
    }

    public function update(Request $request, ResultMessage $resultMessage): RedirectResponse
    {
        $resultMessage->update($this->validated($request));

        return redirect()->route('admin.result-messages.index')->with('success', 'Result message updated successfully.');
    }

    public function destroy(ResultMessage $resultMessage): RedirectResponse
    {
        $resultMessage->delete();

        return redirect()->route('admin.result-messages.index')->with('success', 'Result message deleted successfully.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'campaign_id' => ['nullable', 'exists:campaigns,id'],
            'message' => ['required', 'string'],
            'min_match_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'max_match_percent' => ['required', 'integer', 'min:0', 'max:100', 'gte:min_match_percent'],
            'is_active' => ['sometimes', 'boolean'],
        ]) + [
            'is_active' => $request->boolean('is_active', true),
        ];
    }
}
