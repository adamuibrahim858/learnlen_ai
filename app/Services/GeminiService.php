<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected ?string $apiKey;

    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemma-2-9b-it');
    }

    /**
     * Generate structured learning path with lessons and 5-question quizzes per section.
     */
    public function generateLearningPath(string $content, string $difficulty = 'Intermediate'): array
    {
        $content = trim($content);
        if (empty($content)) {
            throw new \InvalidArgumentException('Content cannot be empty.');
        }

        // If an API key is available, attempt to query the Gemini/Gemma API
        if (! empty($this->apiKey)) {
            try {
                $aiResponse = $this->queryGeminiApi($content, $difficulty);
                if ($aiResponse && isset($aiResponse['sections']) && is_array($aiResponse['sections']) && count($aiResponse['sections']) > 0) {
                    return $this->formatLearningPathData($aiResponse, $difficulty);
                }
            } catch (\Throwable $e) {
                Log::warning('Gemini API call failed, falling back to local curriculum generator: '.$e->getMessage());
            }
        }

        // Fallback: Generate structured lessons and 5 quizzes derived directly from user's text
        return $this->generateFallbackCurriculum($content, $difficulty);
    }

    /**
     * Call the Gemini / Gemma API endpoint.
     */
    protected function queryGeminiApi(string $content, string $difficulty): ?array
    {
        // Truncate content if excessively long to stay well within token bounds
        $trimmedContent = mb_substr($content, 0, 12000);

        $prompt = <<<EOT
You are an expert personalized learning designer and educator for LearnLens AI.
Analyze the following learning material and generate a structured, progressive learning path tailored for {$difficulty} difficulty level.

Break down the material into 3 to 4 sequential, digestible learning sections.
Each section MUST include:
1. "title": A clear, engaging section title.
2. "explanation": A thorough, easy-to-understand lesson explanation breaking down concepts step-by-step.
3. "key_points": An array of 3 to 5 clear takeaways or bullet points.
4. "example": A real-world or practical illustrative example.
5. "quiz": An array of EXACTLY 5 multiple-choice questions testing the content in this section.
   Each quiz question MUST include:
   - "id": integer 1 to 5
   - "question": string
   - "options": object with keys "A", "B", "C", "D"
   - "correct_answer": "A", "B", "C", or "D"
   - "explanation": Brief explanation of why the correct answer is right.

Return ONLY a valid JSON object with the following structure (no markdown formatting, no code fences):
{
  "topic_title": "Short title of the entire topic",
  "summary": "Brief 2-sentence overview",
  "difficulty": "{$difficulty}",
  "sections": [
    {
      "id": 1,
      "title": "Section Title",
      "explanation": "Lesson text...",
      "key_points": ["Point 1", "Point 2", "Point 3"],
      "example": "Real world example...",
      "quiz": [
        {
          "id": 1,
          "question": "Question text?",
          "options": {
            "A": "Option A",
            "B": "Option B",
            "C": "Option C",
            "D": "Option D"
          },
          "correct_answer": "A",
          "explanation": "Why A is correct"
        }
      ]
    }
  ]
}

LEARNING MATERIAL:
{$trimmedContent}
EOT;

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.4,
            ],
        ];

        $response = Http::timeout(35)->post($url, $payload);

        if (! $response->successful()) {
            Log::error('Gemini API Error: '.$response->body());

            return null;
        }

        $data = $response->json();
        $generatedText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (! $generatedText) {
            return null;
        }

        // Clean up markdown formatting if wrapped in ```json ... ```
        $cleanJson = preg_replace('/^```(?:json)?\s*/i', '', trim($generatedText));
        $cleanJson = preg_replace('/\s*```$/', '', $cleanJson);

        $decoded = json_decode($cleanJson, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Standardize and format learning path data.
     */
    protected function formatLearningPathData(array $raw, string $difficulty): array
    {
        $sections = [];
        $rawSections = $raw['sections'] ?? [];

        foreach ($rawSections as $idx => $sec) {
            $quiz = [];
            $rawQuiz = $sec['quiz'] ?? [];

            foreach ($rawQuiz as $qIdx => $q) {
                $options = is_array($q['options'] ?? null) ? $q['options'] : [
                    'A' => 'Option A',
                    'B' => 'Option B',
                    'C' => 'Option C',
                    'D' => 'Option D',
                ];

                $correct = strtoupper((string) ($q['correct_answer'] ?? 'A'));
                if (! in_array($correct, ['A', 'B', 'C', 'D'])) {
                    $correct = 'A';
                }

                $quiz[] = [
                    'id' => $qIdx + 1,
                    'question' => $q['question'] ?? ('Question '.($qIdx + 1).' on '.($sec['title'] ?? 'this topic')),
                    'options' => [
                        'A' => (string) ($options['A'] ?? 'Option A'),
                        'B' => (string) ($options['B'] ?? 'Option B'),
                        'C' => (string) ($options['C'] ?? 'Option C'),
                        'D' => (string) ($options['D'] ?? 'Option D'),
                    ],
                    'correct_answer' => $correct,
                    'explanation' => $q['explanation'] ?? 'This is the correct answer according to the lesson material.',
                ];
            }

            // Ensure exactly 5 quiz questions
            while (count($quiz) < 5) {
                $num = count($quiz) + 1;
                $quiz[] = [
                    'id' => $num,
                    'question' => "What is a primary principle discussed in {$sec['title']}?",
                    'options' => [
                        'A' => 'Applying the core foundational concept systematically',
                        'B' => 'Ignoring structural design and methodology',
                        'C' => 'Limiting implementation to single-purpose scenarios',
                        'D' => 'None of the provided options',
                    ],
                    'correct_answer' => 'A',
                    'explanation' => 'Applying core concepts systematically is central to understanding this section.',
                ];
            }

            $sections[] = [
                'id' => $idx + 1,
                'title' => $sec['title'] ?? ('Section '.($idx + 1)),
                'explanation' => $sec['explanation'] ?? 'Detailed lesson content for this section.',
                'key_points' => is_array($sec['key_points'] ?? null) ? $sec['key_points'] : ['Key takeaway 1', 'Key takeaway 2', 'Key takeaway 3'],
                'example' => $sec['example'] ?? 'For example, observing this in daily applications shows how these principles integrate.',
                'completed' => false,
                'quiz' => array_slice($quiz, 0, 5),
            ];
        }

        return [
            'topic_title' => $raw['topic_title'] ?? 'Personalized Learning Path',
            'summary' => $raw['summary'] ?? 'Custom curriculum generated specifically from your submitted material.',
            'difficulty' => $difficulty,
            'current_unlocked_index' => 0, // 0-based index of unlocked section
            'sections' => $sections,
        ];
    }

    /**
     * Fallback curriculum generator:
     * Parses the user's actual text into cohesive chapters, lessons, key points, examples,
     * and 5 multiple-choice questions per chapter based on key sentences from their text.
     */
    protected function generateFallbackCurriculum(string $content, string $difficulty): array
    {
        $paragraphs = array_values(array_filter(array_map('trim', preg_split('/\n{2,}|\r\n\r\n/', $content))));

        if (count($paragraphs) < 3) {
            // Split by sentences if few paragraphs
            $sentences = preg_split('/(?<=[.?!])\s+/', $content, -1, PREG_SPLIT_NO_EMPTY);
            $chunkSize = max(2, (int) ceil(count($sentences) / 3));
            $chunks = array_chunk($sentences, $chunkSize);
            $paragraphs = array_map(fn ($c) => implode(' ', $c), $chunks);
        }

        $sectionCount = min(4, max(3, count($paragraphs)));
        $chunkSize = (int) ceil(count($paragraphs) / $sectionCount);
        $sectionChunks = array_chunk($paragraphs, $chunkSize);

        $firstSentence = preg_split('/(?<=[.?!])\s+/', $content)[0] ?? 'Custom Learning Material';
        $topicTitle = mb_substr(trim(strip_tags($firstSentence)), 0, 60);
        if (mb_strlen($topicTitle) < 10) {
            $topicTitle = 'Mastering '.($topicTitle ?: 'Your Learning Material');
        }

        $sections = [];

        $defaultThemes = [
            ['title' => 'Core Concepts & Fundamentals', 'icon' => 'bi-lightbulb'],
            ['title' => 'Mechanisms, Principles & Workflow', 'icon' => 'bi-gear-wide-connected'],
            ['title' => 'Practical Applications & Case Study', 'icon' => 'bi-laptop'],
            ['title' => 'Advanced Insights & Best Practices', 'icon' => 'bi-trophy'],
        ];

        foreach ($sectionChunks as $idx => $chunk) {
            if ($idx >= 4) {
                break;
            }

            $chunkText = implode("\n\n", $chunk);
            $theme = $defaultThemes[$idx] ?? ['title' => 'Part '.($idx + 1).': Deeper Understanding'];
            $secTitle = "{$theme['title']} ({$difficulty})";

            // Extract bullet points from text
            $sentencesInChunk = array_values(array_filter(preg_split('/(?<=[.?!])\s+/', $chunkText)));
            $keyPoints = [];
            for ($k = 0; $k < min(4, count($sentencesInChunk)); $k++) {
                $s = trim($sentencesInChunk[$k]);
                if (mb_strlen($s) > 15 && mb_strlen($s) < 200) {
                    $keyPoints[] = $s;
                }
            }

            if (empty($keyPoints)) {
                $keyPoints = [
                    'Understand the foundational definitions and main ideas of this section.',
                    'Analyze how the underlying patterns apply to practical situations.',
                    'Synthesize these concepts to prepare for the progressive mastery quiz.',
                ];
            }

            $exampleText = "Consider applying this directly: When working through the ideas in {$secTitle}, notice how each element connects to build a resilient conceptual framework.";

            // Build 5 contextual quiz questions
            $quiz = $this->buildContextualQuiz($secTitle, $sentencesInChunk, $keyPoints);

            $sections[] = [
                'id' => $idx + 1,
                'title' => $secTitle,
                'explanation' => $chunkText,
                'key_points' => $keyPoints,
                'example' => $exampleText,
                'completed' => false,
                'quiz' => $quiz,
            ];
        }

        return [
            'topic_title' => $topicTitle,
            'summary' => "Structured learning path tailored to {$difficulty} level, designed to take you from fundamentals through to practical mastery.",
            'difficulty' => $difficulty,
            'current_unlocked_index' => 0,
            'sections' => $sections,
        ];
    }

    /**
     * Build 5 multiple-choice questions for fallback curriculum based on text.
     */
    protected function buildContextualQuiz(string $sectionTitle, array $sentences, array $keyPoints): array
    {
        $quiz = [];

        // Question 1: Core Concept
        $point1 = $keyPoints[0] ?? 'The foundational principle of this section';
        $quiz[] = [
            'id' => 1,
            'question' => 'According to this section, which statement accurately reflects the main focus?',
            'options' => [
                'A' => mb_substr($point1, 0, 110),
                'B' => 'The topic strictly avoids any structured methodology or framework.',
                'C' => 'All concepts presented in this section are completely unrelated to one another.',
                'D' => 'It serves only as historical trivia without modern utility.',
            ],
            'correct_answer' => 'A',
            'explanation' => "As stated in the lesson: '{$point1}'.",
        ];

        // Question 2: Application
        $point2 = $keyPoints[1] ?? ($keyPoints[0] ?? 'Sequential learning and active recall');
        $quiz[] = [
            'id' => 2,
            'question' => "What is an essential principle highlighted in '{$sectionTitle}'?",
            'options' => [
                'A' => 'Skipping initial verification steps whenever possible',
                'B' => mb_substr($point2, 0, 110),
                'C' => 'Relying exclusively on random guesswork',
                'D' => 'Eliminating critical review and practical testing',
            ],
            'correct_answer' => 'B',
            'explanation' => "The lesson clearly highlights: '{$point2}'.",
        ];

        // Question 3: Analysis
        $quiz[] = [
            'id' => 3,
            'question' => 'How should a learner effectively apply the concepts in this section?',
            'options' => [
                'A' => 'By memorizing isolated keywords without contextual comprehension',
                'B' => 'By abandoning practical examples in favor of passive skimming',
                'C' => 'By breaking down the material systematically and verifying knowledge through recall',
                'D' => 'By progressing only when all future chapters are immediately skipped',
            ],
            'correct_answer' => 'C',
            'explanation' => 'Systematic analysis and active recall reinforce comprehension and retention.',
        ];

        // Question 4: Critical Thinking
        $point3 = $keyPoints[2] ?? 'Synthesis of underlying ideas into practical execution';
        $quiz[] = [
            'id' => 4,
            'question' => 'Which of the following best represents a key takeaway from this lesson?',
            'options' => [
                'A' => 'Complexity should always be introduced before understanding basic foundations',
                'B' => 'Concepts are best understood when tested in realistic scenarios',
                'C' => 'Only advanced theoretical formulas matter; practical examples are obsolete',
                'D' => 'Evaluation should be avoided at all stages of study',
            ],
            'correct_answer' => 'B',
            'explanation' => 'Testing concepts in realistic scenarios grounds theoretical knowledge in concrete practice.',
        ];

        // Question 5: Synthesis
        $quiz[] = [
            'id' => 5,
            'question' => "What is the expected outcome of successfully mastering '{$sectionTitle}'?",
            'options' => [
                'A' => 'Inability to formulate coherent strategies on this topic',
                'B' => 'Complete dependency on external guidance for trivial tasks',
                'C' => 'The ability to proceed with confidence and apply the principles to subsequent modules',
                'D' => 'Disregarding prior foundational lessons completely',
            ],
            'correct_answer' => 'C',
            'explanation' => 'Mastering this section provides the confidence and foundation required for successive topics.',
        ];

        return $quiz;
    }
}
