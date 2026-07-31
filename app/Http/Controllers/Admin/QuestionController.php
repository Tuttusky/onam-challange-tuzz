<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Question;
use App\Models\QuestionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function index(Campaign $campaign): View
    {
        $questions = $campaign->questions()
            ->with(['category', 'options'])
            ->orderBy('sort_order')
            ->paginate(20);

        return view('admin.questions.index', compact('campaign', 'questions'));
    }

    public function create(Campaign $campaign): View
    {
        $categories = QuestionCategory::query()
            ->where('campaign_id', $campaign->id)
            ->orderBy('sort_order')
            ->get();

        return view('admin.questions.create', compact('campaign', 'categories'));
    }

    public function store(Request $request, Campaign $campaign): RedirectResponse
    {
        $data = $this->validated($request);
        $data['campaign_id'] = $campaign->id;
        $data['sort_order'] = $data['sort_order'] ?? ($campaign->questions()->max('sort_order') + 1);

        Question::query()->create($data);

        return redirect()
            ->route('admin.campaigns.questions.index', $campaign)
            ->with('success', 'Question created successfully.');
    }

    public function edit(Campaign $campaign, Question $question): View
    {
        abort_unless($question->campaign_id === $campaign->id, 404);

        $categories = QuestionCategory::query()
            ->where('campaign_id', $campaign->id)
            ->orderBy('sort_order')
            ->get();

        $question->load('options');

        return view('admin.questions.edit', compact('campaign', 'question', 'categories'));
    }

    public function update(Request $request, Campaign $campaign, Question $question): RedirectResponse
    {
        abort_unless($question->campaign_id === $campaign->id, 404);

        $question->update($this->validated($request));

        return redirect()
            ->route('admin.campaigns.questions.index', $campaign)
            ->with('success', 'Question updated successfully.');
    }

    public function destroy(Campaign $campaign, Question $question): RedirectResponse
    {
        abort_unless($question->campaign_id === $campaign->id, 404);

        $question->delete();

        return redirect()
            ->route('admin.campaigns.questions.index', $campaign)
            ->with('success', 'Question deleted successfully.');
    }

    public function reorder(Request $request, Campaign $campaign): RedirectResponse
    {
        $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:questions,id'],
        ]);

        foreach ($request->input('order') as $index => $questionId) {
            Question::query()
                ->where('campaign_id', $campaign->id)
                ->where('id', $questionId)
                ->update(['sort_order' => $index + 1]);
        }

        return back()->with('success', 'Question order updated.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'category_id' => ['nullable', 'exists:question_categories,id'],
            'type' => ['required', 'in:yes_no,multiple_choice,emoji,text,image,video'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'difficulty' => ['nullable', 'string', 'max:50'],
            'points' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]) + [
            'is_active' => $request->boolean('is_active', true),
            'points' => $request->input('points', 1),
            'difficulty' => $request->input('difficulty', 'medium'),
        ];
    }
}
