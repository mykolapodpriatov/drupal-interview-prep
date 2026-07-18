<?php

/**
 * Structure validator for the question bank (questions/*.md).
 *
 * Enforces the convention documented in CONTRIBUTING.md:
 *
 *   - Each `### Q<n>:` heading is numbered sequentially within its file,
 *     starting at 1, with no gaps and no duplicates.
 *   - Every question carries a `**Tags:**` line and a `**Time:**` line.
 *   - Every bullet inside a `**References:**` block is a well-formed
 *     autolink, e.g. `- <https://www.drupal.org/...>`.
 *
 * Prints one `file:line  message` per violation and exits non-zero when any
 * are found; prints a short summary and exits 0 when the bank is clean.
 *
 * Plain PHP, no Composer dependencies. Run directly:
 *
 *   php scripts/validate-questions.php
 *
 * or via Composer:
 *
 *   composer validate:questions
 */

declare(strict_types=1);

/**
 * A single validation failure, tied to a source location.
 */
final class Violation
{
    public function __construct(
        public readonly string $file,
        public readonly int $line,
        public readonly string $message,
    ) {
    }

    public function __toString(): string
    {
        return sprintf('%s:%d  %s', $this->file, $this->line, $this->message);
    }
}

/**
 * Validate a single question-bank markdown file.
 *
 * @return list<Violation>
 */
function validate_question_file(string $path, string $displayPath): array
{
    $violations = [];

    $contents = file_get_contents($path);
    if ($contents === false) {
        return [new Violation($displayPath, 0, 'unable to read file')];
    }

    // Normalise line endings, then split without stripping so line numbers map 1:1.
    $lines = preg_split('/\r\n|\r|\n/', $contents);
    if ($lines === false) {
        return [new Violation($displayPath, 0, 'unable to parse file')];
    }

    $expectedNumber = 1;
    $inReferences = false;

    /** @var array{num:int,line:int,hasTags:bool,hasTime:bool}|null $current */
    $current = null;
    /** @var list<array{num:int,line:int,hasTags:bool,hasTime:bool}> $questions */
    $questions = [];

    foreach ($lines as $index => $rawLine) {
        $lineNo = $index + 1;
        $line = rtrim($rawLine);

        // Question heading: `### Q<n>: <title>`.
        if (preg_match('/^### Q(\d+):/', $line, $matches) === 1) {
            if ($current !== null) {
                $questions[] = $current;
            }

            $number = (int) $matches[1];
            if ($number !== $expectedNumber) {
                $violations[] = new Violation(
                    $displayPath,
                    $lineNo,
                    sprintf(
                        'expected Q%d but found Q%d (headings must be numbered sequentially with no gaps or duplicates)',
                        $expectedNumber,
                        $number,
                    ),
                );
            }
            // Resync so a single anomaly does not cascade to every later heading.
            $expectedNumber = $number + 1;

            $current = ['num' => $number, 'line' => $lineNo, 'hasTags' => false, 'hasTime' => false];
            $inReferences = false;
            continue;
        }

        // A horizontal rule or a new heading always closes a references block.
        if ($line === '---' || str_starts_with($line, '#')) {
            $inReferences = false;
        }

        // Enter a references block.
        if (preg_match('/^\*\*References:\*\*\s*$/', $line) === 1) {
            $inReferences = true;
            continue;
        }

        // Inside a references block, every bullet must be a bare autolink.
        if ($inReferences) {
            if (str_starts_with($line, '- ')) {
                if (preg_match('#^- <https?://[^\s<>]+>$#', $line) !== 1) {
                    $violations[] = new Violation(
                        $displayPath,
                        $lineNo,
                        sprintf(
                            'malformed reference (expected an autolink like `- <https://example.com>`): %s',
                            trim($line),
                        ),
                    );
                }
                continue;
            }

            // Any non-bullet line ends the references block.
            $inReferences = false;
        }

        // Track presence of the required metadata lines within the question.
        if ($current !== null) {
            if (preg_match('/^\*\*Tags:\*\*/', $line) === 1) {
                $current['hasTags'] = true;
            }
            if (preg_match('/^\*\*Time:\*\*/', $line) === 1) {
                $current['hasTime'] = true;
            }
        }
    }

    if ($current !== null) {
        $questions[] = $current;
    }

    foreach ($questions as $question) {
        if (!$question['hasTags']) {
            $violations[] = new Violation(
                $displayPath,
                $question['line'],
                sprintf('Q%d is missing a **Tags:** line', $question['num']),
            );
        }
        if (!$question['hasTime']) {
            $violations[] = new Violation(
                $displayPath,
                $question['line'],
                sprintf('Q%d is missing a **Time:** line', $question['num']),
            );
        }
    }

    return $violations;
}

// --- Entry point -----------------------------------------------------------

$repoRoot = dirname(__DIR__);
$questionsDir = $repoRoot . '/questions';

if (!is_dir($questionsDir)) {
    fwrite(STDERR, sprintf("error: questions directory not found at %s\n", $questionsDir));
    exit(2);
}

$files = glob($questionsDir . '/*.md');
if ($files === false || $files === []) {
    fwrite(STDERR, sprintf("error: no question files found in %s\n", $questionsDir));
    exit(2);
}
sort($files);

/** @var list<Violation> $violations */
$violations = [];
foreach ($files as $file) {
    $displayPath = 'questions/' . basename($file);
    $violations = array_merge($violations, validate_question_file($file, $displayPath));
}

if ($violations !== []) {
    // Group deterministically by file then line for readable output.
    usort($violations, static function (Violation $a, Violation $b): int {
        return [$a->file, $a->line] <=> [$b->file, $b->line];
    });
    foreach ($violations as $violation) {
        fwrite(STDERR, $violation . "\n");
    }
    fwrite(STDERR, sprintf("\n%d problem(s) found in the question bank.\n", count($violations)));
    exit(1);
}

fwrite(STDOUT, sprintf("questions/*.md: %d file(s) checked, no problems found.\n", count($files)));
exit(0);
