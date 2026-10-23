@extends('layouts.app')

@section('title', (trim(preg_replace('/\s+/', ' ', $data['topic_title'] ?? 'Learning Path'))) . ' - LearnLens AI')

@section('styles')
<style>
    .lesson-formatted-content {
        color: #334155;
        font-size: 1rem;
        line-height: 1.85;
    }

    .lesson-formatted-content p.lesson-paragraph {
        margin-bottom: 1.25rem;
    }

    .topic-header {
        margin-top: 2rem;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e2e8f0;
    }

    .code-window {
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.2);
    }

    .code-window pre code {
        color: #f8fafc;
        font-family: 'JetBrains Mono', 'Fira Code', Consolas, monospace;
    }

    .diagram-window pre {
        font-family: 'JetBrains Mono', Consolas, monospace;
        letter-spacing: 0.5px;
    }

    .callout-card {
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .callout-card:hover {
        transform: translateX(3px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    .key-point-item {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 0.85rem 1rem;
        transition: all 0.2s ease;
    }

    .key-point-item:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
    }
</style>
@endsection

@section('content')
@php
    $sections = $data['sections'] ?? [];
    $totalSections = count($sections);
    $unlockedIndex = $data['current_unlocked_index'] ?? 0;
    $completedCount = count(array_filter($sections, fn($s) => !empty($s['completed'])));
    $progressPercent = $totalSections > 0 ? round(($completedCount / $totalSections) * 100) : 0;
    $allCompleted = ($completedCount === $totalSections && $totalSections > 0);
    $cleanTopicTitle = trim(preg_replace('/\s+/', ' ', $data['topic_title'] ?? 'Your Learning Path'));
    $cleanSummary = trim(preg_replace('/\s+/', ' ', $data['summary'] ?? 'Follow the sequential modules below and pass each checkpoint.'));
@endphp

<div class="container py-4 py-lg-5">
    <!-- Header & Topic Summary -->
    <div class="mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-2">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="{{ route('landing') }}" class="text-decoration-none"><i class="bi bi-house-door"></i> Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Curriculum</li>
                    </ol>
                </nav>
                <h1 class="h2 fw-bold text-dark mb-1" id="topicTitle">{{ $cleanTopicTitle }}</h1>
                <p class="text-secondary mb-0">{{ $cleanSummary }}</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill fw-semibold">
                    <i class="bi bi-mortarboard me-1"></i> {{ $data['difficulty'] ?? 'Intermediate' }} Level
                </span>
                <span class="badge bg-secondary bg-opacity-10 text-secondary border px-3 py-2 rounded-pill fw-semibold">
                    <i class="bi bi-collection me-1"></i> {{ $totalSections }} Modules
                </span>
            </div>
        </div>

        <!-- Progress Bar Card -->
        <div class="card card-custom p-3 mt-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-bold small text-secondary">
                    <i class="bi bi-speedometer2 text-primary me-1"></i> Course Progress
                </span>
                <span class="fw-bold small text-primary" id="progressText">
                    {{ $completedCount }} of {{ $totalSections }} Modules Mastered ({{ $progressPercent }}%)
                </span>
            </div>
            <div class="progress" style="height: 10px; border-radius: 999px; background-color: #e2e8f0;">
                <div 
                    class="progress-bar progress-bar-animated-custom" 
                    id="courseProgressBar" 
                    role="progressbar" 
                    style="width: {{ $progressPercent }}%;" 
                    aria-valuenow="{{ $progressPercent }}" 
                    aria-valuemin="0" 
                    aria-valuemax="100"
                ></div>
            </div>
        </div>

        <!-- Completion Alert (Visible when all modules are finished) -->
        <div id="courseCompletionBanner" class="{{ $allCompleted ? '' : 'd-none' }} alert alert-success border-success border-2 rounded-4 p-4 mt-4 shadow-sm">
            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 text-center text-md-start">
                <div class="d-flex align-items-center gap-3">
                    <div class="fs-1 text-success">
                        <i class="bi bi-award-fill"></i>
                    </div>
                    <div>
                        <h4 class="alert-heading fw-bold mb-1">🎉 Outstanding Achievement! Curriculum Mastered!</h4>
                        <p class="mb-0 text-secondary">You have systematically studied every section and achieved at least 80% on all chapter assessments.</p>
                    </div>
                </div>
                <div>
                    <a href="{{ route('landing') }}" class="btn btn-success rounded-pill px-4 py-2 fw-semibold shadow-sm">
                        <i class="bi bi-plus-circle me-1"></i> Start New Topic
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Learning Sections Container -->
    <div class="row g-4" id="sectionsContainer">
        @foreach($sections as $index => $section)
            @php
                $isCompleted = !empty($section['completed']);
                $isUnlocked = ($index <= $unlockedIndex);
                $cleanSecTitle = trim(preg_replace('/\s+/', ' ', $section['title']));
            @endphp

            <div class="col-12" id="section-wrapper-{{ $section['id'] }}">
                <div class="section-card p-4 p-md-5 {{ $isCompleted ? 'completed' : ($isUnlocked ? 'active-unlocked' : 'locked') }}" id="section-card-{{ $section['id'] }}">
                    <!-- Card Top Header -->
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-3 border-bottom">
                        <div class="d-flex align-items-center gap-3">
                            <span class="step-number {{ $isCompleted ? 'completed' : ($isUnlocked ? 'active' : 'locked') }}" id="badge-{{ $section['id'] }}">
                                @if($isCompleted)
                                    <i class="bi bi-check-lg"></i>
                                @else
                                    {{ $index + 1 }}
                                @endif
                            </span>
                            <div>
                                <h3 class="h4 fw-bold mb-0 text-dark">
                                    {{ $cleanSecTitle }}
                                </h3>
                                <small class="text-muted">Section {{ $index + 1 }} of {{ $totalSections }}</small>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <div id="status-tag-{{ $section['id'] }}">
                            @if($isCompleted)
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill fw-semibold">
                                    <i class="bi bi-patch-check-fill me-1"></i> Mastered
                                </span>
                            @elseif($isUnlocked)
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill fw-semibold">
                                    <i class="bi bi-unlock-fill me-1"></i> Available Now
                                </span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border px-3 py-2 rounded-pill fw-semibold">
                                    <i class="bi bi-lock-fill me-1"></i> Locked &mdash; Complete Previous Quiz
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Card Body Content -->
                    <div class="section-content-body {{ $isUnlocked ? '' : 'd-none' }}" id="section-body-{{ $section['id'] }}">
                        <!-- 1. Lesson Explanation -->
                        <div class="mb-5">
                            <h5 class="fw-bold text-dark d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                                <i class="bi bi-book-half text-primary"></i> Detailed Lesson Notes
                            </h5>
                            <div class="lesson-formatted-content">
                                {!! \App\Services\LessonFormatter::format($section['explanation']) !!}
                            </div>
                        </div>

                        <!-- 2. Key Takeaways -->
                        @if(!empty($section['key_points']))
                            <div class="mb-5 p-4 rounded-4" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                                <h6 class="fw-bold text-dark d-flex align-items-center gap-2 mb-3">
                                    <i class="bi bi-check2-circle text-success fs-5"></i> Key Points to Remember
                                </h6>
                                <div class="row g-2">
                                    @foreach($section['key_points'] as $point)
                                        <div class="col-12 col-md-6">
                                            <div class="key-point-item d-flex align-items-start gap-2 h-100">
                                                <i class="bi bi-check-circle-fill text-primary mt-1 flex-shrink-0"></i>
                                                <span class="small text-dark fw-medium">{{ trim(preg_replace('/\s+/', ' ', $point)) }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- 3. Practical Example -->
                        @if(!empty($section['example']))
                            <div class="mb-5">
                                <h6 class="fw-bold text-dark d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-lightbulb-fill text-warning"></i> Illustrative Example
                                </h6>
                                <div class="p-3 rounded-4 border-start border-4 border-warning shadow-sm" style="background-color: #fffbeb; line-height: 1.7; font-size: 0.95rem;">
                                    {!! \App\Services\LessonFormatter::format($section['example']) !!}
                                </div>
                            </div>
                        @endif

                        <!-- 4. Action Area: Take Quiz Button or Review Quiz -->
                        <div class="pt-4 border-top d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="text-muted small">
                                <i class="bi bi-shield-check text-primary me-1"></i> 5 multiple-choice questions &bull; Requires 80% (4/5) to unlock next section
                            </div>
                            <div>
                                <button 
                                    type="button" 
                                    class="btn {{ $isCompleted ? 'btn-outline-success' : 'btn-gradient' }} rounded-pill px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2 open-quiz-btn"
                                    data-section-id="{{ $section['id'] }}"
                                    data-section-title="{{ $cleanSecTitle }}"
                                >
                                    <i class="bi {{ $isCompleted ? 'bi-patch-check-fill' : 'bi-patch-question-fill' }}"></i>
                                    <span>{{ $isCompleted ? 'Retake Section Quiz' : 'Take Section Quiz' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Locked State Message -->
                    <div class="section-locked-message text-center py-5 {{ $isUnlocked ? 'd-none' : '' }}" id="locked-msg-{{ $section['id'] }}">
                        <div class="display-6 text-muted mb-2"><i class="bi bi-lock-fill"></i></div>
                        <h5 class="fw-bold text-secondary mb-1">Section Gated</h5>
                        <p class="text-muted small mb-0">Score at least 80% on Section {{ $index }} to unlock this lesson and its quiz.</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- ========================================== -->
<!-- QUIZ MODAL (Interactive 5-Question Modal)  -->
<!-- ========================================== -->
<div class="modal fade" id="quizModal" tabindex="-1" aria-labelledby="quizModalTitle" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header border-bottom py-3 px-4" style="background: var(--surface-bg);">
                <div>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1 fw-semibold small mb-1">
                        Mastery Assessment
                    </span>
                    <h5 class="modal-title fw-bold text-dark" id="quizModalTitle">Section Quiz</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4">
                <!-- Quiz Instructions -->
                <div class="alert alert-light border rounded-3 p-3 mb-4 d-flex align-items-center gap-3">
                    <i class="bi bi-shield-check text-primary fs-3"></i>
                    <div class="small">
                        <strong>Pass Requirement:</strong> Select the best answer for all 5 questions. You must score at least <strong>4 out of 5 (80%)</strong> to pass and unlock subsequent modules.
                    </div>
                </div>

                <!-- Live Results Alert (Shown after submission) -->
                <div id="quizFeedbackBox" class="d-none alert rounded-3 p-3 mb-4">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i id="feedbackIcon" class="bi fs-4"></i>
                        <h6 class="fw-bold mb-0" id="feedbackTitle">Results</h6>
                    </div>
                    <div id="feedbackMessage" class="small mb-2"></div>
                    <div id="feedbackScoreProgress" class="progress mt-2" style="height: 8px;">
                        <div id="feedbackProgressBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>

                <!-- Quiz Questions Form -->
                <form id="quizForm">
                    <input type="hidden" id="currentQuizSectionId" name="section_id" value="">
                    
                    <div id="quizQuestionsContainer" class="d-flex flex-column gap-4">
                        <!-- Questions will be dynamically inserted here via JS -->
                    </div>
                </form>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer border-top px-4 py-3 bg-white d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal" id="modalCloseBtn">
                    Close
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary rounded-pill px-4 d-none" id="retryQuizBtn">
                        <i class="bi bi-arrow-repeat me-1"></i> Try Again
                    </button>
                    <button type="button" class="btn btn-gradient rounded-pill px-4" id="submitQuizBtn">
                        <i class="bi bi-send-check me-1"></i> Submit Assessment
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Pass initial sections data safely (without correct_answer exposed for security)
const sectionsRaw = @json($sections);

// Sanitize quiz data to ensure correct_answer is never visible in frontend DOM
const sanitizedSections = sectionsRaw.map(sec => {
    return {
        id: sec.id,
        title: sec.title,
        completed: Boolean(sec.completed),
        quiz: (sec.quiz || []).map(q => ({
            id: q.id,
            question: q.question,
            options: q.options
        }))
    };
});

let currentSectionId = null;
const quizModalEl = document.getElementById('quizModal');
const quizModal = new bootstrap.Modal(quizModalEl);

document.addEventListener('DOMContentLoaded', function () {
    const questionsContainer = document.getElementById('quizQuestionsContainer');
    const submitBtn = document.getElementById('submitQuizBtn');
    const retryBtn = document.getElementById('retryQuizBtn');
    const feedbackBox = document.getElementById('quizFeedbackBox');
    const feedbackTitle = document.getElementById('feedbackTitle');
    const feedbackIcon = document.getElementById('feedbackIcon');
    const feedbackMessage = document.getElementById('feedbackMessage');
    const feedbackProgressBar = document.getElementById('feedbackProgressBar');

    // Attach click handlers to all "Take Quiz" buttons
    document.querySelectorAll('.open-quiz-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const secId = parseInt(this.getAttribute('data-section-id'));
            const secTitle = this.getAttribute('data-section-title');
            openQuizForSection(secId, secTitle);
        });
    });

    function openQuizForSection(secId, secTitle) {
        currentSectionId = secId;
        document.getElementById('currentQuizSectionId').value = secId;
        document.getElementById('quizModalTitle').textContent = `Quiz: ${secTitle}`;

        // Reset UI
        feedbackBox.classList.add('d-none');
        retryBtn.classList.add('d-none');
        submitBtn.classList.remove('d-none');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-send-check me-1"></i> Submit Assessment';

        // Render questions
        const sec = sanitizedSections.find(s => s.id === secId);
        if (!sec || !sec.quiz || sec.quiz.length === 0) {
            questionsContainer.innerHTML = '<div class="alert alert-warning">No questions found for this section.</div>';
            return;
        }

        let html = '';
        sec.quiz.forEach((q, idx) => {
            html += `
                <div class="card border rounded-3 p-3 shadow-none question-card" id="q-card-${q.id}">
                    <div class="fw-bold text-dark mb-3 d-flex align-items-start gap-2">
                        <span class="badge bg-primary text-white rounded-pill px-2 py-1 small">${idx + 1}</span>
                        <span>${escapeHtml(q.question)}</span>
                    </div>
                    <div class="options-group d-flex flex-column gap-2" id="options-group-${q.id}">
            `;

            ['A', 'B', 'C', 'D'].forEach(optKey => {
                const optText = q.options && q.options[optKey] ? q.options[optKey] : '';
                html += `
                    <div>
                        <input type="radio" class="quiz-option-input d-none" name="q_${q.id}" id="q_${q.id}_${optKey}" value="${optKey}">
                        <label class="quiz-option-label" for="q_${q.id}_${optKey}" id="label_${q.id}_${optKey}">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-light text-dark border px-2 py-1 rounded">${optKey}</span>
                                <span>${escapeHtml(optText)}</span>
                            </div>
                        </label>
                    </div>
                `;
            });

            html += `
                    </div>
                    <div class="explanation-box d-none mt-2 p-2 rounded small" id="exp-${q.id}"></div>
                </div>
            `;
        });

        questionsContainer.innerHTML = html;
        quizModal.show();
    }

    // Submit Quiz Handler
    submitBtn.addEventListener('click', function () {
        const sec = sanitizedSections.find(s => s.id === currentSectionId);
        if (!sec) return;

        // Collect answers
        const answers = {};
        let allAnswered = true;

        sec.quiz.forEach(q => {
            const selected = document.querySelector(`input[name="q_${q.id}"]:checked`);
            if (selected) {
                answers[q.id] = selected.value;
            } else {
                allAnswered = false;
            }
        });

        if (!allAnswered) {
            alert('Please answer all 5 questions before submitting!');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Scoring...';

        // Call backend server validation
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch('{{ route("quiz.submit") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                section_id: currentSectionId,
                answers: answers
            })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert(data.message || 'Error scoring quiz.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-send-check me-1"></i> Submit Assessment';
                return;
            }

            // Display feedback
            feedbackBox.classList.remove('d-none');
            feedbackMessage.textContent = data.feedback;
            feedbackProgressBar.style.width = data.percentage + '%';

            if (data.passed) {
                feedbackBox.className = 'alert alert-success rounded-3 p-3 mb-4';
                feedbackTitle.textContent = `Passed! (${data.score}/5 - ${data.percentage}%)`;
                feedbackIcon.className = 'bi bi-check-circle-fill text-success fs-4';
                feedbackProgressBar.className = 'progress-bar bg-success';
                submitBtn.classList.add('d-none');
                retryBtn.classList.add('d-none');

                // Update section in client state
                updateSectionPassedUI(currentSectionId, data.unlocked_index, data.all_completed);
            } else {
                feedbackBox.className = 'alert alert-warning rounded-3 p-3 mb-4';
                feedbackTitle.textContent = `Needs Review (${data.score}/5 - ${data.percentage}%)`;
                feedbackIcon.className = 'bi bi-exclamation-circle-fill text-warning fs-4';
                feedbackProgressBar.className = 'progress-bar bg-warning';
                submitBtn.classList.add('d-none');
                retryBtn.classList.remove('d-none');
            }

            // Annotate each question with explanation and correct/wrong colors
            if (data.results && Array.isArray(data.results)) {
                data.results.forEach(res => {
                    const qCard = document.getElementById(`q-card-${res.id}`);
                    const expBox = document.getElementById(`exp-${res.id}`);
                    const userLabel = document.getElementById(`label_${res.id}_${res.user_answer}`);
                    const correctLabel = document.getElementById(`label_${res.id}_${res.correct_answer}`);

                    if (res.is_correct) {
                        if (userLabel) {
                            userLabel.style.borderColor = '#22c55e';
                            userLabel.style.backgroundColor = '#f0fdf4';
                        }
                    } else {
                        if (userLabel) {
                            userLabel.style.borderColor = '#ef4444';
                            userLabel.style.backgroundColor = '#fef2f2';
                        }
                        if (correctLabel) {
                            correctLabel.style.borderColor = '#22c55e';
                            correctLabel.style.backgroundColor = '#f0fdf4';
                        }
                    }

                    if (expBox) {
                        expBox.classList.remove('d-none');
                        expBox.className = res.is_correct ? 'explanation-box mt-2 p-2 rounded small bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'explanation-box mt-2 p-2 rounded small bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25';
                        expBox.innerHTML = `<strong>${res.is_correct ? '✓ Correct:' : '✗ Incorrect (Correct is ' + res.correct_answer + '):'}</strong> ${escapeHtml(res.explanation)}`;
                    }

                    // Disable inputs after scoring
                    document.querySelectorAll(`input[name="q_${res.id}"]`).forEach(input => input.disabled = true);
                });
            }
        })
        .catch(err => {
            console.error('Quiz submit error:', err);
            alert('A network error occurred while submitting your assessment. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-send-check me-1"></i> Submit Assessment';
        });
    });

    // Retry Handler
    retryBtn.addEventListener('click', function () {
        const sec = sanitizedSections.find(s => s.id === currentSectionId);
        if (sec) {
            openQuizForSection(sec.id, sec.title);
        }
    });

    // Dynamic UI Update on Passing Quiz
    function updateSectionPassedUI(sectionId, unlockedIndex, allCompleted) {
        // 1. Mark current section card as completed
        const card = document.getElementById(`section-card-${sectionId}`);
        const badge = document.getElementById(`badge-${sectionId}`);
        const statusTag = document.getElementById(`status-tag-${sectionId}`);

        if (card) {
            card.classList.remove('active-unlocked');
            card.classList.add('completed');
        }
        if (badge) {
            badge.className = 'step-number completed';
            badge.innerHTML = '<i class="bi bi-check-lg"></i>';
        }
        if (statusTag) {
            statusTag.innerHTML = `
                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill fw-semibold">
                    <i class="bi bi-patch-check-fill me-1"></i> Mastered
                </span>
            `;
        }

        // Update local object
        const localSec = sanitizedSections.find(s => s.id === sectionId);
        if (localSec) localSec.completed = true;

        // 2. Unlock the next section if unlockedIndex was bumped
        if (unlockedIndex < sanitizedSections.length) {
            const nextSec = sanitizedSections[unlockedIndex];
            if (nextSec) {
                const nextCard = document.getElementById(`section-card-${nextSec.id}`);
                const nextBadge = document.getElementById(`badge-${nextSec.id}`);
                const nextStatusTag = document.getElementById(`status-tag-${nextSec.id}`);
                const nextBody = document.getElementById(`section-body-${nextSec.id}`);
                const nextLockedMsg = document.getElementById(`locked-msg-${nextSec.id}`);

                if (nextCard && !nextCard.classList.contains('completed')) {
                    nextCard.classList.remove('locked');
                    nextCard.classList.add('active-unlocked');
                    if (nextBadge) nextBadge.className = 'step-number active';
                    if (nextStatusTag) {
                        nextStatusTag.innerHTML = `
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill fw-semibold">
                                <i class="bi bi-unlock-fill me-1"></i> Available Now
                            </span>
                        `;
                    }
                    if (nextBody) nextBody.classList.remove('d-none');
                    if (nextLockedMsg) nextLockedMsg.classList.add('d-none');
                }
            }
        }

        // 3. Recalculate progress bar
        const total = sanitizedSections.length;
        const completedCount = sanitizedSections.filter(s => s.completed).length;
        const percent = total > 0 ? Math.round((completedCount / total) * 100) : 0;

        const progressBar = document.getElementById('courseProgressBar');
        const progressText = document.getElementById('progressText');

        if (progressBar) progressBar.style.width = percent + '%';
        if (progressText) progressText.textContent = `${completedCount} of ${total} Modules Mastered (${percent}%)`;

        // 4. Show Completion banner if all completed
        if (allCompleted || completedCount === total) {
            const banner = document.getElementById('courseCompletionBanner');
            if (banner) {
                banner.classList.remove('d-none');
                banner.scrollIntoView({ behavior: 'smooth' });
            }
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
@endsection
