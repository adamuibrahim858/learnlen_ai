<?php

namespace App\Http\Controllers;

use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Smalot\PdfParser\Parser as PdfParser;

class LearningController extends Controller
{
    public function __construct(
        protected GeminiService $geminiService
    ) {}

    /**
     * Display Landing Page (Page 1).
     */
    public function index(): View
    {
        $hasActiveSession = session()->has('learnlens_data');
        $activeTopic = $hasActiveSession ? session('learnlens_data.topic_title') : null;

        return view('landing', compact('hasActiveSession', 'activeTopic'));
    }

    /**
     * Process learning material submission and generate learning path.
     */
    public function generate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'learning_material' => 'nullable|string|max:80000',
            'pdf_file' => 'nullable|file|mimes:pdf|max:12288', // max 12MB
            'difficulty' => 'required|string|in:Beginner,Intermediate,Advanced',
        ]);

        $material = trim($validated['learning_material'] ?? '');

        // Extract text from PDF if uploaded
        if ($request->hasFile('pdf_file')) {
            try {
                $parser = new PdfParser;
                $pdf = $parser->parseFile($request->file('pdf_file')->getPathname());
                $extractedText = trim($pdf->getText());

                if (! empty($extractedText)) {
                    $material = ! empty($material) ? ($material."\n\n".$extractedText) : $extractedText;
                }
            } catch (\Throwable $e) {
                return back()
                    ->withInput()
                    ->withErrors(['pdf_file' => 'Could not read PDF contents: '.$e->getMessage()]);
            }
        }

        if (empty($material)) {
            return back()
                ->withInput()
                ->withErrors(['learning_material' => 'Please provide learning material via the text area or upload a valid PDF document.']);
        }

        try {
            $learningData = $this->geminiService->generateLearningPath($material, $validated['difficulty']);

            session(['learnlens_data' => $learningData]);

            return redirect()->route('learning.path')->with('success', 'Your personalized learning path has been successfully generated!');
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['learning_material' => 'Error generating curriculum: '.$e->getMessage()]);
        }
    }

    /**
     * Display Learning Page (Page 2).
     */
    public function learningPath(): View|RedirectResponse
    {
        $data = session('learnlens_data');

        if (! $data || empty($data['sections'])) {
            return redirect()->route('landing')->with('info', 'Please submit learning material first to generate your path.');
        }

        return view('learning', compact('data'));
    }

    /**
     * Submit and score a quiz for a section.
     */
    public function submitQuiz(Request $request): JsonResponse
    {
        $request->validate([
            'section_id' => 'required|integer',
            'answers' => 'required|array',
        ]);

        $data = session('learnlens_data');
        if (! $data || empty($data['sections'])) {
            return response()->json([
                'success' => false,
                'message' => 'No active learning session found. Please reload.',
            ], 404);
        }

        $sectionId = (int) $request->input('section_id');
        $userAnswers = $request->input('answers');

        // Locate the target section
        $sectionIndex = null;
        foreach ($data['sections'] as $idx => $sec) {
            if ($sec['id'] === $sectionId) {
                $sectionIndex = $idx;
                break;
            }
        }

        if ($sectionIndex === null) {
            return response()->json([
                'success' => false,
                'message' => 'Section not found.',
            ], 404);
        }

        // Verify section is unlocked
        $currentUnlocked = $data['current_unlocked_index'] ?? 0;
        if ($sectionIndex > $currentUnlocked) {
            return response()->json([
                'success' => false,
                'message' => 'This section is locked. Please pass the prerequisite quiz first.',
            ], 403);
        }

        $section = $data['sections'][$sectionIndex];
        $quiz = $section['quiz'] ?? [];

        $score = 0;
        $totalQuestions = count($quiz);
        $results = [];

        foreach ($quiz as $q) {
            $qid = $q['id'];
            $submittedAnswer = strtoupper(trim((string) ($userAnswers[$qid] ?? '')));
            $correctAnswer = strtoupper(trim((string) ($q['correct_answer'] ?? 'A')));
            $isCorrect = ($submittedAnswer === $correctAnswer);

            if ($isCorrect) {
                $score++;
            }

            $results[] = [
                'id' => $qid,
                'question' => $q['question'],
                'user_answer' => $submittedAnswer,
                'correct_answer' => $correctAnswer,
                'is_correct' => $isCorrect,
                'explanation' => $q['explanation'] ?? '',
            ];
        }

        $percentage = $totalQuestions > 0 ? round(($score / $totalQuestions) * 100) : 0;
        $passed = ($score >= 4); // Need at least 4 out of 5 (80%) to pass

        $allCompleted = false;

        if ($passed) {
            // Mark current section completed
            $data['sections'][$sectionIndex]['completed'] = true;

            // Unlock next section if available
            if ($currentUnlocked === $sectionIndex && ($sectionIndex + 1) < count($data['sections'])) {
                $data['current_unlocked_index'] = $sectionIndex + 1;
            }

            // Check if all sections are completed
            $completedCount = count(array_filter($data['sections'], fn ($s) => ! empty($s['completed'])));
            $allCompleted = ($completedCount === count($data['sections']));

            // Save back to session
            session(['learnlens_data' => $data]);
        }

        $feedback = $passed
            ? "Outstanding work! You scored {$score}/{$totalQuestions} ({$percentage}%). Section mastered!"
            : "You scored {$score}/{$totalQuestions} ({$percentage}%). A passing score of at least 4/5 (80%) is required to unlock the next section. Review the feedback and try again!";

        return response()->json([
            'success' => true,
            'passed' => $passed,
            'score' => $score,
            'total' => $totalQuestions,
            'percentage' => $percentage,
            'feedback' => $feedback,
            'results' => $results,
            'unlocked_index' => $data['current_unlocked_index'] ?? 0,
            'all_completed' => $allCompleted,
        ]);
    }

    /**
     * Reset current learning session to start fresh.
     */
    public function reset(): RedirectResponse
    {
        session()->forget('learnlens_data');

        return redirect()->route('landing')->with('info', 'Learning path reset. You can now start a new topic.');
    }
}
