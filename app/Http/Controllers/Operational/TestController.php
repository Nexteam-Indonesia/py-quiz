<?php

namespace App\Http\Controllers\Operational;

use App\Contract\Master\CourseContract;
use App\Http\Controllers\Controller;
use App\Jobs\EvaluateTestAnswer;
use App\Models\Test;
use App\Models\TestAnswer;
use App\Models\TestAttempt;
use App\Models\User;
use App\Service\Operational\TestAnswerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TestController extends Controller
{
    protected CourseContract $courseService;
    protected TestAnswerService $testAnswerService;

    public function __construct(CourseContract $courseService, TestAnswerService $testAnswerService)
    {
        $this->courseService = $courseService;
        $this->testAnswerService = $testAnswerService;
    }

    public function start(Test $test)
    {
        if ($test->status !== 'published' || ($test->available_from && !now()->isBetween($test->available_from, $test->available_until))) {
            abort(403, 'This test is not available.');
        }

        if (in_array($test->type, [Test::POST_TEST, Test::DELAY_TEST])) {
            $classGroup = $this->courseService->getUserClassGroup($test->course);

            if ($classGroup === User::GROUP_EXP) {
                $allModulesCompleted = $this->courseService->areAllModulesCompleted($test->course);
                abort_if(!$allModulesCompleted, 403, 'You must complete all modules with a score of 80+ to start this test.');
            }
        }

        $existingAttempt = TestAttempt::where('test_id', $test->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($existingAttempt) {
            if ($existingAttempt->finished_at) {
                abort(403, 'You have already completed this test and cannot retake it.');
            } else {
                return redirect()->route('operational.test.take', ['attempt' => $existingAttempt->id]);
            }
        }
        
        $attempt = TestAttempt::create([
            'test_id' => $test->id,
            'user_id' => Auth::id(),
            'started_at' => now(),
        ]);

        return redirect()->route('operational.test.take', ['attempt' => $attempt->id]);
    }

    public function take(TestAttempt $attempt)
    {
        abort_if($attempt->user_id !== Auth::id() || $attempt->finished_at, 403);
        
        $attempt->load('test.question');
        
        if ($attempt->test->question) {
            $attempt->test->question->makeHidden(['test']);
        }

        return Inertia::render('operational/test/take', [
            'attempt' => $attempt,
        ]);
    }

    public function submit(Request $request, TestAttempt $attempt)
    {
        abort_if($attempt->user_id !== Auth::id() || $attempt->finished_at, 403);

        $validated = $request->validate(['student_code' => 'required|string']);

        $updatedAttempt = EvaluateTestAnswer::dispatch(
            $attempt,
            $validated['student_code']
        );

        return response()->json([
            'message' => 'Test successfully evaluated.',
            'data' => $updatedAttempt
        ], 200);
    }

    public function result(TestAttempt $attempt)
    {
        abort_if($attempt->user_id !== Auth::id(), 403);
        
        $attempt->load('test.question');

        return Inertia::render('operational/test/review', [
            'attempt' => $attempt,
        ]);
    }
}
