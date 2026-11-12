<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Questions\ManageQuestions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderQuestionRequest;
use App\Http\Requests\Admin\SaveQuestionRequest;
use App\Models\Exam;
use App\Models\Exercise;
use App\Models\Question;
use App\Support\AccountPages;
use App\Support\QuestionType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function __construct(private ManageQuestions $questions) {}

    public function exercises(): View
    {
        Gate::authorize('manage', Exercise::class);

        return AccountPages::view('A16', 'list', [
            'owners' => Exercise::with('unit.level')->orderBy('title')->orderBy('id')->paginate(20),
            'resource' => 'exercises',
        ], 'questions.owners');
    }

    public function exams(): View
    {
        Gate::authorize('manage', Exam::class);

        return AccountPages::view('A20', 'list', [
            'owners' => Exam::with('unit.level')->where('type', Exam::INTERACTIVE)->orderBy('title')->orderBy('id')->paginate(20),
            'resource' => 'exams',
        ], 'questions.owners');
    }

    public function index(Exercise|Exam $owner): View
    {
        return $this->page($owner, 'list', ['records' => $owner->questions()->ordered()->get()]);
    }

    public function create(Exercise|Exam $owner): View
    {
        return $this->editor($owner, new Question(['type' => QuestionType::SingleChoice, 'points' => 2]));
    }

    public function edit(Exercise|Exam $owner, Question $question): View
    {
        return $this->editor($owner, $question);
    }

    private function editor(Exercise|Exam $owner, Question $question): View
    {
        return $this->page($owner, 'editor', [
            'question' => $question,
            'definition' => [
                'content' => old('content', $question->content),
                'answer' => old('answer', $question->answer),
            ],
        ]);
    }

    private function page(Exercise|Exam $owner, string $panel, array $data): View
    {
        $exam = $owner instanceof Exam;
        $ownerId = $exam ? 'A20' : 'A16';
        $listId = $exam ? 'A23' : 'A18';
        $prefix = $this->prefix($owner);

        $crumbs = [$ownerId];
        $links = [$listId => ['url' => route($prefix.'.index', $owner)]];
        if ($exam) {
            $crumbs[] = 'A22';
            $links['A22'] = ['url' => route('admin.curriculum.exams.edit', $owner), 'title' => $owner->title];
        }
        if ($panel !== 'list') {
            $crumbs[] = $listId;
        }

        return AccountPages::view($panel === 'list' ? $listId : ($exam ? 'A24' : 'A19'), $panel, $data + [
            'owner' => $owner,
            'prefix' => $prefix,
            'description' => $owner->title,
            'crumbs' => $crumbs,
            'links' => $links,
        ], 'questions.'.$panel);
    }

    private function prefix(Exercise|Exam $owner): string
    {
        return $owner instanceof Exam ? 'admin.curriculum.exams.questions' : 'admin.curriculum.exercises.questions';
    }

    public function store(SaveQuestionRequest $request, Exercise|Exam $owner): JsonResponse|RedirectResponse
    {
        $question = $this->questions->save($owner, $request->validated());

        return AccountPages::html($request) ? $this->backToList($owner, 'Question added.') : response()->json($question, 201);
    }

    public function update(SaveQuestionRequest $request, Exercise|Exam $owner, Question $question): JsonResponse|RedirectResponse
    {
        $updated = $this->questions->save($owner, $request->validated(), $question->id);

        return AccountPages::html($request) ? $this->backToList($owner, 'Question saved.') : response()->json($updated);
    }

    public function destroy(Request $request, Exercise|Exam $owner, Question $question): Response|RedirectResponse
    {
        $this->questions->remove($owner, $question->id);

        return AccountPages::html($request) ? $this->backToList($owner, 'Question removed.') : response()->noContent();
    }

    public function reorder(ReorderQuestionRequest $request, Exercise|Exam $owner, Question $question): Response|RedirectResponse
    {
        $this->questions->reorder($owner, $question->id, $request->integer('position'));

        return AccountPages::html($request) ? $this->backToList($owner, 'Question order updated.') : response()->noContent();
    }

    private function backToList(Exercise|Exam $owner, string $status): RedirectResponse
    {
        return redirect()->route($this->prefix($owner).'.index', $owner, 303)->with('status', $status);
    }
}
