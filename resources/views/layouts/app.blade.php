<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'LearnLens AI - Turn Any Material into Your Personal Learning Journey')</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #2563eb 100%);
            --accent-glow: rgba(99, 102, 241, 0.15);
            --card-border: #e2e8f0;
            --surface-bg: #f8fafc;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--surface-bg);
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar-brand-badge {
            background: var(--primary-gradient);
            color: white;
            font-weight: 800;
            letter-spacing: -0.5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);
        }

        .hero-badge {
            background: rgba(79, 70, 229, 0.1);
            color: #4f46e5;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 0.4rem 1rem;
            border-radius: 50rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid rgba(79, 70, 229, 0.2);
        }

        .hero-title {
            font-weight: 800;
            letter-spacing: -1px;
            line-height: 1.2;
            color: #0f172a;
        }

        .hero-title .gradient-text {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .card-custom {
            border: 1px solid var(--card-border);
            border-radius: 16px;
            background: #ffffff;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
            transition: all 0.25s ease;
        }

        .card-custom:hover {
            box-shadow: 0 10px 30px -4px rgba(15, 23, 42, 0.08);
        }

        .btn-gradient {
            background: var(--primary-gradient);
            color: #ffffff;
            font-weight: 600;
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3);
            transition: all 0.2s ease;
        }

        .btn-gradient:hover {
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
        }

        .btn-gradient:active {
            transform: translateY(0);
        }

        .difficulty-radio input[type="radio"] {
            display: none;
        }

        .difficulty-radio label {
            cursor: pointer;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            text-align: center;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            width: 100%;
            background: #fff;
            color: #475569;
        }

        .difficulty-radio input[type="radio"]:checked + label {
            border-color: #4f46e5;
            background: rgba(79, 70, 229, 0.06);
            color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
        }

        .section-card {
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .section-card.locked {
            background: #f8fafc;
            opacity: 0.75;
            border-style: dashed;
        }

        .section-card.completed {
            border-color: #86efac;
            background: linear-gradient(to bottom, #f0fdf4 0%, #ffffff 15%);
        }

        .section-card.active-unlocked {
            border-color: #818cf8;
            box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.15);
        }

        .step-number {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.95rem;
        }

        .step-number.locked {
            background: #e2e8f0;
            color: #64748b;
        }

        .step-number.active {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);
        }

        .step-number.completed {
            background: #22c55e;
            color: white;
            box-shadow: 0 4px 10px rgba(34, 197, 94, 0.3);
        }

        .quiz-option-label {
            display: block;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #ffffff;
            margin-bottom: 0.5rem;
        }

        .quiz-option-label:hover {
            border-color: #c7d2fe;
            background: #f5f7ff;
        }

        .quiz-option-input:checked + .quiz-option-label {
            border-color: #4f46e5;
            background: #eef2ff;
            color: #312e81;
            font-weight: 600;
        }

        .code-example {
            background: #0f172a;
            color: #e2e8f0;
            border-radius: 12px;
            padding: 1rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9rem;
            overflow-x: auto;
        }

        .progress-bar-animated-custom {
            background: var(--primary-gradient);
            transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
    @yield('styles')
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="{{ route('landing') }}">
                <span class="navbar-brand-badge">
                    <i class="bi bi-mortarboard-fill"></i>
                </span>
                <span class="fs-4 tracking-tight" style="color: #0f172a;">LearnLens<span style="color: #4f46e5;">.AI</span></span>
            </a>

            <div class="d-flex align-items-center gap-2 ms-auto">
                @if(session()->has('learnlens_data'))
                    <a href="{{ route('learning.path') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-semibold d-inline-flex align-items-center gap-1">
                        <i class="bi bi-journal-bookmark-fill"></i> Current Path
                    </a>
                    <form action="{{ route('learning.reset') }}" method="POST" class="d-inline" onsubmit="return confirm('Start a new topic? This will clear your current progress.');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 fw-semibold" title="Clear current session">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </button>
                    </form>
                @endif
                <span class="badge bg-light text-secondary border rounded-pill px-3 py-2 fw-medium d-none d-md-inline-block">
                    <i class="bi bi-cpu text-primary me-1"></i> Gemma / Gemini Powered
                </span>
            </div>
        </div>
    </nav>

    <!-- Global Flash Alerts -->
    <div class="container mt-3">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center" role="alert">
                <i class="bi bi-check-circle-fill fs-5 me-2 text-success"></i>
                <div>{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center" role="alert">
                <i class="bi bi-info-circle-fill fs-5 me-2 text-info"></i>
                <div>{{ session('info') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0" role="alert">
                <div class="d-flex align-items-center mb-1">
                    <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-danger"></i>
                    <strong>Action Required:</strong>
                </div>
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    </div>

    <!-- Main Content -->
    <main class="flex-grow-1">
        @yield('content')
    </main>

    <!-- Minimal Clean Footer -->
    <footer class="bg-white border-top py-4 mt-5 text-center text-muted small">
        <div class="container d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
            <div>
                <strong>LearnLens AI</strong> &mdash; Structured, mastery-based personalized learning.
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="text-secondary"><i class="bi bi-shield-check text-success me-1"></i> Client Key Protected</span>
                <span class="text-secondary"><i class="bi bi-lightning-charge text-warning me-1"></i> Adaptive Progression</span>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5.3 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    @yield('scripts')
</body>
</html>
