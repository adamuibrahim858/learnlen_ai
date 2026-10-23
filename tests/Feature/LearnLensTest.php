<?php

namespace Tests\Feature;

use Tests\TestCase;

class LearnLensTest extends TestCase
{
    public function test_landing_page_renders_successfully(): void
    {
        $response = $this->get(route('landing'));

        $response->assertStatus(200);
        $response->assertSee('Turn Any Material into Your');
        $response->assertSee('Personal Learning Journey');
        $response->assertSee('Generate Learning Path');
    }

    public function test_cannot_generate_without_material(): void
    {
        $response = $this->post(route('learning.generate'), [
            'learning_material' => '',
            'difficulty' => 'Beginner',
        ]);

        $response->assertSessionHasErrors(['learning_material']);
    }

    public function test_can_generate_learning_path_with_valid_text(): void
    {
        $material = "Quantum computing relies on qubits. Unlike traditional bits that represent 0 or 1, qubits exploit superposition to represent combinations of both 0 and 1 simultaneously.\n\nQuantum entanglement connects qubits instantaneously regardless of spatial separation.\n\nDecoherence occurs when environmental noise destabilizes quantum coherence. Cryogenic dilution refrigerators maintain near-absolute zero temperatures to preserve states.";

        $response = $this->post(route('learning.generate'), [
            'learning_material' => $material,
            'difficulty' => 'Intermediate',
        ]);

        $response->assertRedirect(route('learning.path'));
        $this->assertTrue(session()->has('learnlens_data'));

        $data = session('learnlens_data');
        $this->assertNotEmpty($data['sections']);
        $this->assertEquals(0, $data['current_unlocked_index']);
        $this->assertEquals('Intermediate', $data['difficulty']);

        // Check that each section has 5 quiz questions
        foreach ($data['sections'] as $sec) {
            $this->assertCount(5, $sec['quiz']);
            $this->assertNotEmpty($sec['explanation']);
            $this->assertNotEmpty($sec['key_points']);
            $this->assertNotEmpty($sec['example']);
        }
    }

    public function test_learning_page_redirects_if_session_empty(): void
    {
        $response = $this->get(route('learning.path'));

        $response->assertRedirect(route('landing'));
    }

    public function test_learning_page_displays_content_with_session(): void
    {
        $mockData = [
            'topic_title' => 'Python Basics',
            'summary' => 'Introduction to Python.',
            'difficulty' => 'Beginner',
            'current_unlocked_index' => 0,
            'sections' => [
                [
                    'id' => 1,
                    'title' => 'Variables & Data Types',
                    'explanation' => 'Variables store data.',
                    'key_points' => ['Point 1', 'Point 2'],
                    'example' => 'x = 10',
                    'completed' => false,
                    'quiz' => [
                        [
                            'id' => 1,
                            'question' => 'Q1',
                            'options' => ['A' => 'Opt A', 'B' => 'Opt B', 'C' => 'Opt C', 'D' => 'Opt D'],
                            'correct_answer' => 'A',
                            'explanation' => 'A is correct',
                        ],
                    ],
                ],
                [
                    'id' => 2,
                    'title' => 'Control Flow',
                    'explanation' => 'Conditionals and loops.',
                    'key_points' => ['Point 3'],
                    'example' => 'if x > 5: pass',
                    'completed' => false,
                    'quiz' => [],
                ],
            ],
        ];

        session(['learnlens_data' => $mockData]);

        $response = $this->get(route('learning.path'));

        $response->assertStatus(200);
        $response->assertSee('Python Basics');
        $response->assertSee('Variables &amp; Data Types', false);
        $response->assertSee('Control Flow');
    }

    public function test_quiz_fails_if_score_below_80_percent(): void
    {
        $mockData = [
            'topic_title' => 'Sample Course',
            'difficulty' => 'Beginner',
            'current_unlocked_index' => 0,
            'sections' => [
                [
                    'id' => 1,
                    'title' => 'Sec 1',
                    'completed' => false,
                    'quiz' => [
                        ['id' => 1, 'question' => 'Q1', 'correct_answer' => 'A', 'explanation' => 'Exp 1'],
                        ['id' => 2, 'question' => 'Q2', 'correct_answer' => 'A', 'explanation' => 'Exp 2'],
                        ['id' => 3, 'question' => 'Q3', 'correct_answer' => 'A', 'explanation' => 'Exp 3'],
                        ['id' => 4, 'question' => 'Q4', 'correct_answer' => 'A', 'explanation' => 'Exp 4'],
                        ['id' => 5, 'question' => 'Q5', 'correct_answer' => 'A', 'explanation' => 'Exp 5'],
                    ],
                ],
                [
                    'id' => 2,
                    'title' => 'Sec 2',
                    'completed' => false,
                    'quiz' => [],
                ],
            ],
        ];

        session(['learnlens_data' => $mockData]);

        // Submit only 2 correct answers out of 5 (40% < 80%)
        $response = $this->postJson(route('quiz.submit'), [
            'section_id' => 1,
            'answers' => [
                1 => 'A', // correct
                2 => 'A', // correct
                3 => 'B', // wrong
                4 => 'C', // wrong
                5 => 'D', // wrong
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'passed' => false,
            'score' => 2,
            'total' => 5,
            'percentage' => 40,
        ]);

        // Section 2 must remain locked (current_unlocked_index stays 0)
        $this->assertEquals(0, session('learnlens_data.current_unlocked_index'));
        $this->assertFalse(session('learnlens_data.sections.0.completed'));
    }

    public function test_quiz_passes_with_at_least_4_out_of_5_and_unlocks_next_section(): void
    {
        $mockData = [
            'topic_title' => 'Sample Course',
            'difficulty' => 'Beginner',
            'current_unlocked_index' => 0,
            'sections' => [
                [
                    'id' => 1,
                    'title' => 'Sec 1',
                    'completed' => false,
                    'quiz' => [
                        ['id' => 1, 'question' => 'Q1', 'correct_answer' => 'A', 'explanation' => 'Exp 1'],
                        ['id' => 2, 'question' => 'Q2', 'correct_answer' => 'B', 'explanation' => 'Exp 2'],
                        ['id' => 3, 'question' => 'Q3', 'correct_answer' => 'C', 'explanation' => 'Exp 3'],
                        ['id' => 4, 'question' => 'Q4', 'correct_answer' => 'D', 'explanation' => 'Exp 4'],
                        ['id' => 5, 'question' => 'Q5', 'correct_answer' => 'A', 'explanation' => 'Exp 5'],
                    ],
                ],
                [
                    'id' => 2,
                    'title' => 'Sec 2',
                    'completed' => false,
                    'quiz' => [],
                ],
            ],
        ];

        session(['learnlens_data' => $mockData]);

        // Submit 4 correct answers out of 5 (80% >= 80%)
        $response = $this->postJson(route('quiz.submit'), [
            'section_id' => 1,
            'answers' => [
                1 => 'A', // correct
                2 => 'B', // correct
                3 => 'C', // correct
                4 => 'D', // correct
                5 => 'B', // wrong
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'passed' => true,
            'score' => 4,
            'total' => 5,
            'percentage' => 80,
            'unlocked_index' => 1,
        ]);

        // Section 1 must be marked completed and Section 2 unlocked!
        $this->assertTrue(session('learnlens_data.sections.0.completed'));
        $this->assertEquals(1, session('learnlens_data.current_unlocked_index'));
    }

    public function test_reset_route_clears_session(): void
    {
        session(['learnlens_data' => ['topic_title' => 'To be deleted']]);

        $response = $this->post(route('learning.reset'));

        $response->assertRedirect(route('landing'));
        $this->assertFalse(session()->has('learnlens_data'));
    }
}
