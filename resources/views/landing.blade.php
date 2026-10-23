@extends('layouts.app')

@section('title', 'LearnLens AI - Turn Any Material into Your Personal Learning Journey')

@section('content')
<div class="container py-4 py-lg-5">
    <!-- Hero Section -->
    <div class="text-center mb-5 max-w-75 mx-auto">
        <div class="mb-3">
            <span class="hero-badge">
                <i class="bi bi-stars"></i> AI-Powered Mastery Learning
            </span>
        </div>
        <h1 class="hero-title display-5 fw-bold mb-3">
            Turn Any Material into Your <span class="gradient-text">Personal Learning Journey</span>.
        </h1>
        <p class="lead text-secondary mx-auto" style="max-width: 680px;">
            Paste any article, textbook excerpt, notes, or upload a PDF. LearnLens AI constructs a progressive learning path with interactive lessons and mastery quizzes for every section.
        </p>

        @if($hasActiveSession)
            <div class="alert alert-light border rounded-4 d-inline-flex align-items-center gap-3 p-3 shadow-sm mt-3">
                <i class="bi bi-clock-history text-primary fs-4"></i>
                <div class="text-start">
                    <div class="fw-semibold text-dark">You have an ongoing path: {{ $activeTopic }}</div>
                    <small class="text-muted">You can jump right back in or create a brand new curriculum below.</small>
                </div>
                <a href="{{ route('learning.path') }}" class="btn btn-primary btn-sm rounded-pill px-3 fw-semibold">
                    Resume Path &rarr;
                </a>
            </div>
        @endif
    </div>

    <!-- Main Generator Form Card -->
    <div class="row justify-content-center">
        <div class="col-lg-9 col-xl-8">
            <div class="card card-custom p-4 p-md-5">
                <form action="{{ route('learning.generate') }}" method="POST" enctype="multipart/form-data" id="learningForm">
                    @csrf

                    <!-- 1. Textarea for Learning Material -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label for="learning_material" class="form-label fw-bold mb-0">
                                <i class="bi bi-file-text me-1 text-primary"></i> 1. Paste Your Learning Material
                            </label>
                            <span class="text-muted small" id="charCount">0 characters</span>
                        </div>
                        <textarea 
                            class="form-control @error('learning_material') is-invalid @enderror" 
                            id="learning_material" 
                            name="learning_material" 
                            rows="7" 
                            placeholder="Paste text, notes, research papers, syllabus, or lecture transcripts here... (e.g. quantum computing, machine learning, cell biology, personal finance)"
                            style="border-radius: 12px; font-size: 0.95rem; resize: vertical;"
                        >{{ old('learning_material') }}</textarea>
                        
                        <!-- Quick Sample Topics Buttons -->
                        <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                            <span class="text-muted small fw-semibold"><i class="bi bi-lightning-fill text-warning"></i> Quick Presets:</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-1 px-3 sample-btn" data-preset="quantum">
                                ⚛️ Quantum Computing Basics
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-1 px-3 sample-btn" data-preset="ai_ethics">
                                🤖 AI Ethics & Safety
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-1 px-3 sample-btn" data-preset="finance">
                                📈 Personal Finance & Compound Interest
                            </button>
                        </div>
                    </div>

                    <!-- 2. Optional PDF Upload Field -->
                    <div class="mb-4">
                        <label for="pdf_file" class="form-label fw-bold d-flex align-items-center justify-content-between mb-2">
                            <span><i class="bi bi-file-earmark-pdf me-1 text-danger"></i> 2. Or Upload a PDF Document <span class="badge bg-light text-secondary border fw-normal">Optional</span></span>
                            <small class="text-muted">Max 12MB</small>
                        </label>
                        <div class="input-group">
                            <input 
                                type="file" 
                                class="form-control @error('pdf_file') is-invalid @enderror" 
                                id="pdf_file" 
                                name="pdf_file" 
                                accept=".pdf"
                                style="border-radius: 12px;"
                            >
                        </div>
                        <div class="form-text text-muted mt-1">
                            Uploaded PDFs are parsed on the server and merged with any pasted text.
                        </div>
                    </div>

                    <!-- 3. Difficulty Selector -->
                    <div class="mb-4">
                        <label class="form-label fw-bold mb-2">
                            <i class="bi bi-sliders me-1 text-primary"></i> 3. Select Target Difficulty Level
                        </label>
                        <div class="row g-3 difficulty-radio">
                            <div class="col-4">
                                <input type="radio" name="difficulty" id="diff_beginner" value="Beginner" {{ old('difficulty', 'Beginner') === 'Beginner' ? 'checked' : '' }}>
                                <label for="diff_beginner" class="d-flex flex-column align-items-center py-3">
                                    <span class="fs-4 mb-1">🌱</span>
                                    <span>Beginner</span>
                                    <small class="text-muted fw-normal">Foundational & accessible</small>
                                </label>
                            </div>
                            <div class="col-4">
                                <input type="radio" name="difficulty" id="diff_intermediate" value="Intermediate" {{ old('difficulty') === 'Intermediate' ? 'checked' : '' }}>
                                <label for="diff_intermediate" class="d-flex flex-column align-items-center py-3">
                                    <span class="fs-4 mb-1">⚡</span>
                                    <span>Intermediate</span>
                                    <small class="text-muted fw-normal">Practical & balanced</small>
                                </label>
                            </div>
                            <div class="col-4">
                                <input type="radio" name="difficulty" id="diff_advanced" value="Advanced" {{ old('difficulty') === 'Advanced' ? 'checked' : '' }}>
                                <label for="diff_advanced" class="d-flex flex-column align-items-center py-3">
                                    <span class="fs-4 mb-1">🚀</span>
                                    <span>Advanced</span>
                                    <small class="text-muted fw-normal">In-depth & rigorous</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Submit Button -->
                    <div class="pt-2">
                        <button type="submit" class="btn btn-gradient w-100 py-3 fs-5 d-flex align-items-center justify-content-center gap-2" id="submitBtn">
                            <i class="bi bi-magic"></i>
                            <span>Generate Learning Path</span>
                        </button>
                    </div>

                    <!-- Loading State Indicator (Hidden by default) -->
                    <div id="loadingOverlay" class="d-none text-center py-4 mt-3 rounded-3" style="background: rgba(79, 70, 229, 0.05); border: 1px dashed rgba(79, 70, 229, 0.3);">
                        <div class="spinner-border text-primary mb-2" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div class="fw-semibold text-primary">Generating your customized learning path...</div>
                        <small class="text-secondary">Analyzing core concepts, creating lesson notes, and formulating quiz questions.</small>
                    </div>
                </form>
            </div>

            <!-- Features Highlights Grid -->
            <div class="row g-4 mt-4 text-center">
                <div class="col-md-4">
                    <div class="p-3">
                        <div class="fs-3 mb-2 text-primary"><i class="bi bi-diagram-3"></i></div>
                        <h6 class="fw-bold">Step-by-Step Paths</h6>
                        <p class="small text-muted mb-0">Deconstructs complex materials into sequential, bite-sized learning cards.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3">
                        <div class="fs-3 mb-2 text-success"><i class="bi bi-shield-lock"></i></div>
                        <h6 class="fw-bold">Mastery Gating</h6>
                        <p class="small text-muted mb-0">Prove comprehension by scoring 80% (4/5) on quizzes to unlock the next chapter.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3">
                        <div class="fs-3 mb-2 text-warning"><i class="bi bi-arrow-repeat"></i></div>
                        <h6 class="fw-bold">Instant Explanations</h6>
                        <p class="small text-muted mb-0">Receive immediate feedback on every question with unlimited retries.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const textarea = document.getElementById('learning_material');
    const charCount = document.getElementById('charCount');
    const form = document.getElementById('learningForm');
    const submitBtn = document.getElementById('submitBtn');
    const loadingOverlay = document.getElementById('loadingOverlay');

    // Update character count
    function updateCount() {
        const count = textarea.value.length;
        charCount.textContent = count.toLocaleString() + ' characters';
    }
    textarea.addEventListener('input', updateCount);
    updateCount();

    // Sample Presets for instantaneous testing
    const samplePresets = {
        quantum: `Quantum computing harnesses the unique phenomena of quantum mechanics to deliver huge leaps forward in processing power. Traditional computers encode information into binary bits that can either be 0s or 1s. In contrast, quantum computers utilize qubits. Thanks to a property known as superposition, qubits can exist in a multidimensional state where they represent combinations of both 0 and 1 simultaneously.

Another core principle is quantum entanglement. When qubits become entangled, the quantum state of one qubit instantaneously dictates the state of another, no matter the physical distance between them. This allows quantum processors to correlate vast amounts of information in parallel.

However, quantum systems are extremely fragile. Decoherence occurs when external environmental noise, such as temperature fluctuations or electromagnetic interference, disrupts the quantum state, introducing computational errors. Researchers use error-correcting codes and cryogenic cooling chambers near absolute zero to stabilize these fragile states. Modern applications include molecular simulation for pharmaceutical discovery, cryptanalysis, and high-dimensional combinatorial optimization.`,

        ai_ethics: `Artificial Intelligence ethics focuses on ensuring that machine learning systems are designed, deployed, and monitored in ways that are fair, transparent, and beneficial to society. 

Algorithmic bias is one of the most critical challenges. Machine learning models train on historical datasets. If those datasets contain systemic historical biases or under-represent certain demographics, the resulting algorithms can automate and scale unfair discrimination in lending, hiring, and predictive policing. Mitigation strategies include diverse training datasets, fairness-aware loss functions, and adversarial debiasing.

Explainability and transparency (often referred to as Explainable AI or XAI) address the "black box" problem. Deep neural networks often make decisions without human-interpretable reasoning. In high-stakes areas like healthcare diagnostics or judicial sentencing, stakeholders need interpretable models to verify rationale and hold systems accountable. Furthermore, data privacy and consent must be protected using techniques such as differential privacy and federated learning, ensuring user data is never harvested without consent.`,

        finance: `Personal financial independence rests on three foundational pillars: cash flow management, debt control, and systematic compound investing.

Cash flow budgeting follows the 50/30/20 rule: 50% of take-home income is allocated to essential needs (housing, utilities, food), 30% to discretionary wants, and 20% to savings and investment vehicles. Maintaining an emergency fund covering 3 to 6 months of living expenses in an easily accessible liquid account safeguards against unexpected shocks without needing high-interest credit.

Compound interest is the mathematical accelerator of long-term wealth. Albert Einstein famously called compound interest the eighth wonder of the world: those who understand it earn it, and those who do not pay it. When earnings generate their own earnings over decades, exponential growth takes place. For example, investing $500 monthly at an annualized 8% return yields over $300,000 in 20 years, with the majority of the final balance stemming from compounded gains rather than raw contributions. Diversified index funds that track broad market indices minimize individual stock risks while maximizing low-cost diversification.`
    };

    document.querySelectorAll('.sample-btn').forEach(button => {
        button.addEventListener('click', function () {
            const presetKey = this.getAttribute('data-preset');
            if (samplePresets[presetKey]) {
                textarea.value = samplePresets[presetKey];
                updateCount();
                textarea.focus();
                // Flash textarea highlight
                textarea.style.borderColor = '#4f46e5';
                textarea.style.boxShadow = '0 0 0 4px rgba(79, 70, 229, 0.2)';
                setTimeout(() => {
                    textarea.style.borderColor = '';
                    textarea.style.boxShadow = '';
                }, 800);
            }
        });
    });

    // Form submission loading state
    form.addEventListener('submit', function (e) {
        const textVal = textarea.value.trim();
        const pdfInput = document.getElementById('pdf_file');
        const hasPdf = pdfInput.files && pdfInput.files.length > 0;

        if (!textVal && !hasPdf) {
            e.preventDefault();
            alert('Please provide some learning text or select a PDF file before generating!');
            textarea.focus();
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Generating Learning Path...';
        loadingOverlay.classList.remove('d-none');
    });
});
</script>
@endsection
