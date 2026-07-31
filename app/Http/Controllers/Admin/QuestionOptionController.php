<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuestionOptionController extends Controller
{
    public function store(Request $request, Question $question): RedirectResponse
    {
        $data = $this->validated($request);
        $data['question_id'] = $question->id;
        $data['sort_order'] = $data['sort_order'] ?? ($question->options()->max('sort_order') + 1);

        QuestionOption::query()->create($data);

        return back()->with('success', 'Option added successfully.');
    }

    public function update(Request $request, Question $question, QuestionOption $option): RedirectResponse
    {
        abort_unless($option->question_id === $question->id, 404);

        $option->update($this->validated($request));

        return back()->with('success', 'Option updated successfully.');
    }

    public function destroy(Question $question, QuestionOption $option): RedirectResponse
    {
        abort_unless($option->question_id === $question->id, 404);

        $option->delete();

        return back()->with('success', 'Option deleted successfully.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'value' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'string', 'max:255'],
            'points' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]) + [
            'is_active' => $request->boolean('is_active', true),
            'points' => $request->input('points', 0),
        ];
    }
}
