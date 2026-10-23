<?php

namespace App\Services;

class LessonFormatter
{
    /**
     * Format lesson explanation into clean, structured, responsive HTML.
     */
    public static function format(?string $content): string
    {
        if (empty($content)) {
            return '<p class="text-muted">No lesson content provided for this section.</p>';
        }

        // 1. Clean PDF pagination and repetitive artifacts
        $text = str_replace(["\r\n", "\r"], "\n", $content);
        $text = preg_replace('/[^\n]*Lecture Note[^\n]*\nPage \d+ of \d+\s*\n/i', "\n\n", $text);
        $text = preg_replace('/Page \d+ of \d+/i', '', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", trim($text));

        // 2. Pre-split on major semantic boundary markers if found within larger blocks
        $text = preg_replace('/(?<!\n\n)(Topic\s+\d+:|Diagram\s+\d+:|Review\s+questions|PHP\s+in\s+\d+\s+seconds|Definition:|The\s+idea\s+in\s+plain\s+English|School\s+example:|AI\s+prompt\s+to\s+try:)/i', "\n\n$1", $text);

        // 3. Split into blocks by double newlines
        $rawBlocks = preg_split('/\n{2,}/', $text);
        $htmlBlocks = [];

        foreach ($rawBlocks as $block) {
            $block = trim($block);
            if (empty($block)) {
                continue;
            }

            // A. Check if block is a Code Block (starts with <?php or triple backticks or contains multiple code lines)
            if (self::isCodeBlock($block)) {
                $htmlBlocks[] = self::renderCodeBlock($block);

                continue;
            }

            // B. Check if block is an ASCII Diagram or Flowchart
            if (self::isDiagramBlock($block)) {
                $htmlBlocks[] = self::renderDiagramBlock($block);

                continue;
            }

            // C. Check if block is a Special Callout (Topic heading, Definition, Idea, Example, AI prompt, Review)
            $calloutHtml = self::tryRenderCallout($block);
            if ($calloutHtml !== null) {
                $htmlBlocks[] = $calloutHtml;

                continue;
            }

            // D. Check for standard Markdown Headings
            if (preg_match('/^#{1,4}\s+(.+)$/m', $block)) {
                $htmlBlocks[] = self::renderMarkdownHeadings($block);

                continue;
            }

            // E. Standard Prose Paragraph - unwrap jagged PDF line breaks and parse inline markdown
            $unwrapped = self::unwrapJaggedLines($block);
            $parsedText = self::parseInlineMarkdown($unwrapped);
            $htmlBlocks[] = '<p class="lesson-paragraph mb-3" style="line-height: 1.85; color: #334155; font-size: 1.025rem;">'.$parsedText.'</p>';
        }

        return implode("\n", $htmlBlocks);
    }

    /**
     * Detect if a text block is code.
     */
    protected static function isCodeBlock(string $block): bool
    {
        if (str_starts_with($block, '```') || str_contains($block, '<?php')) {
            return true;
        }

        $lines = explode("\n", $block);
        $codeScore = 0;
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (preg_match('/^\$[a-zA-Z_]|function\s+\w+|class\s+\w+|return\s+|echo\s+|namespace\s+|use\s+App|Route::|Schema::|DB::|public\s+function|protected\s+|private\s+|->|=>|[{}\[\];]/', $trimmed)) {
                $codeScore++;
            }
        }

        return count($lines) >= 2 && ($codeScore / count($lines)) >= 0.45;
    }

    /**
     * Render a code block with macOS-style dark header and copy button.
     */
    protected static function renderCodeBlock(string $block): string
    {
        // Strip fences if any
        $code = preg_replace('/^```(?:php)?\s*/i', '', $block);
        $code = preg_replace('/\s*```$/', '', $code);

        // Extract title if first line is a title like "PHP in 60 seconds"
        $lines = explode("\n", $code);
        $title = 'PHP Code Snippet';
        if (isset($lines[0]) && ! str_starts_with(trim($lines[0]), '<?php') && ! str_starts_with(trim($lines[0]), '$') && strlen(trim($lines[0])) < 40) {
            $title = htmlspecialchars(trim($lines[0]));
            array_shift($lines);
            $code = implode("\n", $lines);
        }

        $escaped = htmlspecialchars(trim($code));

        return <<<HTML
<div class="code-window card border-0 shadow-sm rounded-4 overflow-hidden mb-4" style="background-color: #0f172a;">
    <div class="code-window-header px-4 py-2 d-flex align-items-center justify-content-between" style="background-color: #1e293b; border-bottom: 1px solid rgba(255,255,255,0.08);">
        <div class="d-flex align-items-center gap-2">
            <span class="rounded-circle" style="width: 10px; height: 10px; background-color: #ef4444; display: inline-block;"></span>
            <span class="rounded-circle" style="width: 10px; height: 10px; background-color: #f59e0b; display: inline-block;"></span>
            <span class="rounded-circle" style="width: 10px; height: 10px; background-color: #10b981; display: inline-block;"></span>
            <span class="ms-2 fw-semibold small text-light font-monospace">{$title}</span>
        </div>
        <button type="button" class="btn btn-sm btn-outline-light py-0 px-2 rounded small" style="font-size: 0.75rem;" onclick="navigator.clipboard.writeText(this.closest('.code-window').querySelector('code').innerText); this.innerText='Copied!'; setTimeout(() => this.innerText='Copy', 1500);">
            <i class="bi bi-clipboard me-1"></i>Copy
        </button>
    </div>
    <pre class="m-0 p-4 text-light font-monospace" style="overflow-x: auto; line-height: 1.6; font-size: 0.92rem; background-color: #0f172a;"><code>{$escaped}</code></pre>
</div>
HTML;
    }

    /**
     * Detect if a text block is an ASCII flowchart or diagram.
     */
    protected static function isDiagramBlock(string $block): bool
    {
        $lines = explode("\n", $block);
        if (count($lines) < 3) {
            return false;
        }

        $diagramScore = 0;
        foreach ($lines as $line) {
            if (preg_match('#(\||\b\w+\s*-->|\+--|v\b|--->|<---|/\\\\|\\\\/)#', $line)) {
                $diagramScore++;
            }
        }

        return ($diagramScore / count($lines)) >= 0.3 || str_contains($block, 'Diagram 1:') || str_contains($block, 'Diagram 2:') || str_contains($block, 'Diagram 3:');
    }

    /**
     * Render an ASCII diagram nicely.
     */
    protected static function renderDiagramBlock(string $block): string
    {
        $lines = explode("\n", $block);
        $title = 'Architecture & Workflow Diagram';

        if (isset($lines[0]) && preg_match('/Diagram\s*\d*[:\s]+(.*)/i', $lines[0], $m)) {
            $title = trim($lines[0]);
            array_shift($lines);
            $block = implode("\n", $lines);
        }

        $escaped = htmlspecialchars(trim($block));

        return <<<HTML
<div class="diagram-window card border-0 rounded-4 overflow-hidden mb-4 shadow-sm" style="border: 1px solid #334155 !important;">
    <div class="px-4 py-2 border-bottom d-flex align-items-center gap-2 fw-bold small text-light" style="background-color: #1e293b;">
        <i class="bi bi-diagram-3-fill text-info"></i> {$title}
    </div>
    <pre class="m-0 p-4 font-monospace text-info small" style="overflow-x: auto; line-height: 1.6; font-size: 0.9rem; background-color: #0b1329 !important;">{$escaped}</pre>
</div>
HTML;
    }

    /**
     * Format special callout boxes (Topic headings, Definition, Example, AI prompt, Review).
     */
    protected static function tryRenderCallout(string $block): ?string
    {
        // 1. Topic Title (e.g. "Topic 1: Introduction to Backend Development")
        if (preg_match('/^Topic\s+(\d+)[:\s]+(.*)$/im', $block, $matches)) {
            $topicNum = $matches[1];
            $topicName = htmlspecialchars(trim($matches[2]));

            return <<<HTML
<div class="topic-header mt-4 mb-3 pb-2 border-bottom d-flex align-items-center gap-2">
    <span class="badge bg-primary text-white rounded-pill px-3 py-2 fw-bold">Topic {$topicNum}</span>
    <h4 class="h5 fw-bold text-dark mb-0">{$topicName}</h4>
</div>
HTML;
        }

        // 2. Definition Callout
        if (preg_match('/^Definition:\s*(.+)$/is', $block, $matches)) {
            $defText = self::parseInlineMarkdown(self::unwrapJaggedLines(trim($matches[1])));

            return <<<HTML
<div class="card border-0 rounded-4 mb-3 p-3 shadow-sm" style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.08) 0%, rgba(99, 102, 241, 0.03) 100%); border-left: 4px solid #4f46e5 !important;">
    <div class="d-flex align-items-center gap-2 mb-2">
        <span class="badge bg-primary text-white rounded-pill px-2 py-1 small">
            <i class="bi bi-bookmark-check-fill me-1"></i> Definition
        </span>
    </div>
    <div class="text-dark fw-semibold" style="line-height: 1.75; font-size: 1rem;">
        {$defText}
    </div>
</div>
HTML;
        }

        // 3. "The idea in plain English"
        if (preg_match('/^The idea in plain English\s*\n*(.+)$/is', $block, $matches)) {
            $content = self::parseInlineMarkdown(self::unwrapJaggedLines(trim($matches[1])));

            return <<<HTML
<div class="card border-0 rounded-4 mb-3 p-3 shadow-sm" style="background: linear-gradient(135deg, rgba(6, 182, 212, 0.08) 0%, rgba(14, 165, 233, 0.03) 100%); border-left: 4px solid #06b6d4 !important;">
    <div class="d-flex align-items-center gap-2 mb-2">
        <span class="badge bg-info text-dark rounded-pill px-2 py-1 small fw-bold">
            <i class="bi bi-lightbulb-fill me-1"></i> Intuition / Plain English
        </span>
    </div>
    <div class="text-dark" style="line-height: 1.75; font-size: 0.98rem;">
        {$content}
    </div>
</div>
HTML;
        }

        // 4. School Example / Practical Example
        if (preg_match('/^(?:School\s+example|Example):\s*(.+)$/is', $block, $matches)) {
            $exText = self::parseInlineMarkdown(self::unwrapJaggedLines(trim($matches[1])));

            return <<<HTML
<div class="card border-0 rounded-4 mb-3 p-3 shadow-sm" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.08) 0%, rgba(251, 191, 36, 0.03) 100%); border-left: 4px solid #f59e0b !important;">
    <div class="d-flex align-items-center gap-2 mb-2">
        <span class="badge bg-warning text-dark rounded-pill px-2 py-1 small fw-bold">
            <i class="bi bi-laptop me-1"></i> Practical Example
        </span>
    </div>
    <div class="text-dark" style="line-height: 1.75; font-size: 0.98rem;">
        {$exText}
    </div>
</div>
HTML;
        }

        // 5. AI Prompt to Try
        if (preg_match('/^AI prompt to try:\s*(.+)$/is', $block, $matches)) {
            $promptText = self::parseInlineMarkdown(self::unwrapJaggedLines(trim($matches[1])));

            return <<<HTML
<div class="card border-0 rounded-4 mb-3 p-3 shadow-sm" style="background: linear-gradient(135deg, rgba(168, 85, 247, 0.08) 0%, rgba(192, 132, 252, 0.03) 100%); border-left: 4px solid #a855f7 !important;">
    <div class="d-flex align-items-center gap-2 mb-2">
        <span class="badge text-white rounded-pill px-2 py-1 small" style="background-color: #8b5cf6;">
            <i class="bi bi-stars me-1"></i> Suggested AI Prompt
        </span>
    </div>
    <div class="fst-italic text-secondary" style="line-height: 1.75;">
        {$promptText}
    </div>
</div>
HTML;
        }

        // 6. Review Questions Block
        if (preg_match('/^Review questions\s*\n*(.+)$/is', $block, $matches)) {
            $lines = explode("\n", trim($matches[1]));
            $listItems = '';
            foreach ($lines as $line) {
                $trimmed = trim(preg_replace('/^\d+\.\s*/', '', $line));
                if (! empty($trimmed)) {
                    $listItems .= '<li class="mb-2 text-dark">'.self::parseInlineMarkdown($trimmed).'</li>';
                }
            }

            return <<<HTML
<div class="card border rounded-4 mb-3 p-3 bg-light shadow-sm">
    <div class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
        <i class="bi bi-question-circle-fill text-primary"></i> Self-Review Checkpoint
    </div>
    <ol class="mb-0 ps-3 small" style="line-height: 1.75;">
        {$listItems}
    </ol>
</div>
HTML;
        }

        return null;
    }

    /**
     * Render markdown headings.
     */
    protected static function renderMarkdownHeadings(string $block): string
    {
        $lines = explode("\n", $block);
        $output = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (preg_match('/^#\s+(.+)$/', $trimmed, $m)) {
                $output[] = '<h3 class="h4 fw-bold text-dark mt-4 mb-2 pb-1 border-bottom">'.self::parseInlineMarkdown($m[1]).'</h3>';
            } elseif (preg_match('/^##\s+(.+)$/', $trimmed, $m)) {
                $output[] = '<h4 class="h5 fw-bold text-dark mt-3 mb-2">'.self::parseInlineMarkdown($m[1]).'</h4>';
            } elseif (preg_match('/^###\s+(.+)$/', $trimmed, $m)) {
                $output[] = '<h5 class="h6 fw-bold text-primary mt-3 mb-2 text-uppercase tracking-wider">'.self::parseInlineMarkdown($m[1]).'</h5>';
            } elseif (! empty($trimmed)) {
                $output[] = '<p class="lesson-paragraph mb-2" style="line-height: 1.85; color: #334155;">'.self::parseInlineMarkdown($trimmed).'</p>';
            }
        }

        return implode("\n", $output);
    }

    /**
     * Unwraps lines that were broken mid-sentence by PDF line wrapping.
     */
    protected static function unwrapJaggedLines(string $text): string
    {
        $lines = explode("\n", $text);
        $result = [];
        $current = '';

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                if ($current !== '') {
                    $result[] = $current;
                    $current = '';
                }

                continue;
            }

            if ($current === '') {
                $current = $trimmed;
            } else {
                // If previous line ends with hyphen, merge word
                if (str_ends_with($current, '-')) {
                    $current = substr($current, 0, -1).$trimmed;
                } else {
                    $current .= ' '.$trimmed;
                }
            }
        }

        if ($current !== '') {
            $result[] = $current;
        }

        return implode("\n\n", $result);
    }

    /**
     * Parse inline markdown (bold, italic, code, links).
     */
    protected static function parseInlineMarkdown(string $text): string
    {
        // Escape standard HTML first
        $safe = htmlspecialchars($text, ENT_QUOTES, 'UTF-8', false);

        // Convert inline code `foo`
        $safe = preg_replace('/`([^`]+)`/', '<code class="bg-light text-primary px-2 py-0.5 rounded border small font-monospace">$1</code>', $safe);

        // Convert bold **text**
        $safe = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $safe);

        // Convert italic *text*
        $safe = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $safe);

        return $safe;
    }
}
