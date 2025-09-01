<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateDocumentSummary implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $backoff = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Document $document
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Skip if we don't have content to summarize
        if (empty($this->document->content)) {
            Log::info('Skipping summary generation - no content available', [
                'document_id' => $this->document->id,
            ]);

            return;
        }

        try {
            $summary = $this->generateSummary();

            if ($summary) {
                $this->document->update([
                    'summary' => $summary,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to generate summary', [
                'document_id' => $this->document->id,
                'url' => $this->document->url,
                'error' => $e->getMessage(),
            ]);

            // Don't throw exception for summary failures - they're not critical
        }
    }

    private function generateSummary(): ?string
    {
        $content = $this->document->content;
        if (! $content) {
            return null;
        }

        // Clean up the content
        $content = $this->cleanContentForSummary($content);

        // If content is short, skip summary
        if (strlen($content) < 200) {
            return null;
        }

        // Split into sentences - handle common abbreviations
        // First replace common abbreviations with placeholders to avoid splitting
        $content = preg_replace('/\bA\.I\./i', 'PLACEHOLDER_AI', $content);
        $content = preg_replace('/\bU\.S\./i', 'PLACEHOLDER_US', $content);
        $content = preg_replace('/\bU\.K\./i', 'PLACEHOLDER_UK', $content);
        $content = preg_replace('/\bU\.N\./i', 'PLACEHOLDER_UN', $content);
        $content = preg_replace('/\bE\.U\./i', 'PLACEHOLDER_EU', $content);
        $content = preg_replace('/\bMr\./i', 'PLACEHOLDER_MR', $content);
        $content = preg_replace('/\bMs\./i', 'PLACEHOLDER_MS', $content);
        $content = preg_replace('/\bDr\./i', 'PLACEHOLDER_DR', $content);

        // Split on sentence endings
        $sentences = preg_split('/(?<=[.!?])\s+/', $content, -1, PREG_SPLIT_NO_EMPTY);

        // Restore abbreviations in each sentence
        $sentences = array_map(function ($sentence) {
            $sentence = preg_replace('/PLACEHOLDER_AI/i', 'A.I.', $sentence);
            $sentence = preg_replace('/PLACEHOLDER_US/i', 'U.S.', $sentence);
            $sentence = preg_replace('/PLACEHOLDER_UK/i', 'U.K.', $sentence);
            $sentence = preg_replace('/PLACEHOLDER_UN/i', 'U.N.', $sentence);
            $sentence = preg_replace('/PLACEHOLDER_EU/i', 'E.U.', $sentence);
            $sentence = preg_replace('/PLACEHOLDER_MR/i', 'Mr.', $sentence);
            $sentence = preg_replace('/PLACEHOLDER_MS/i', 'Ms.', $sentence);
            $sentence = preg_replace('/PLACEHOLDER_DR/i', 'Dr.', $sentence);

            return $sentence;
        }, $sentences);

        if (count($sentences) === 0) {
            return null;
        }

        // Take first meaningful sentence that's not too short and doesn't repeat the title
        $summary = '';
        $targetLength = 150; // Aim for about 150 characters

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);

            // Skip very short sentences or those that might be headers/dates
            if (strlen($sentence) < 20 || $this->isUnwantedSentence($sentence)) {
                continue;
            }

            // Check if this sentence would make a good summary
            if (strlen($sentence) <= $targetLength) {
                $summary = $sentence;
                break;
            }

            // If sentence is too long, try to find a good breaking point
            if (strlen($sentence) > $targetLength) {
                $words = explode(' ', $sentence);
                $truncated = '';

                foreach ($words as $word) {
                    $potential = $truncated.($truncated ? ' ' : '').$word;
                    if (strlen($potential) > $targetLength - 3) {
                        break;
                    }
                    $truncated = $potential;
                }

                if (strlen($truncated) > 50) {
                    $summary = $truncated.'...';
                    break;
                }
            }
        }

        return $summary ?: null;
    }

    private function cleanContentForSummary(string $content): string
    {
        // Remove HTML tags first
        $content = strip_tags($content);

        // Remove Markdown reference-style links like [](#)
        $content = preg_replace('/\[\]\(#[^)]*\)/', '', $content);

        // Remove Markdown headers (both # style and === style)
        $content = preg_replace('/^#{1,6}\s+.*$/m', '', $content);
        $content = preg_replace('/^.+\n={3,}.*$/m', '', $content);
        $content = preg_replace('/^.+\n-{3,}.*$/m', '', $content);

        // Remove underlined headers (=== and --- patterns)
        $content = preg_replace('/^=+\s*$/m', '', $content);
        $content = preg_replace('/^-+\s*$/m', '', $content);

        // Remove Markdown links but keep the text
        $content = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $content);

        // Remove code blocks
        $content = preg_replace('/```[\s\S]*?```/', '', $content);
        $content = preg_replace('/`[^`]+`/', '', $content);

        // Remove dates that often appear at the start
        $content = preg_replace('/^\s*\d{1,2}\s+(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{4}\s*/i', '', $content);

        // Remove the document title if it appears at the beginning
        $title = $this->document->title;
        if ($title) {
            $escapedTitle = preg_quote($title, '/');
            $content = preg_replace('/^\s*'.$escapedTitle.'\s*/i', '', $content);
        }

        // Remove multiple line breaks and normalize whitespace
        $content = preg_replace('/\n\s*\n/', ' ', $content);
        $content = preg_replace('/\s+/', ' ', $content);

        return trim($content);
    }

    private function isUnwantedSentence(string $sentence): bool
    {
        $sentence = strtolower(trim($sentence));
        $title = strtolower(trim($this->document->title ?? ''));

        // Skip if sentence is similar to the title
        if ($title) {
            if (str_contains($sentence, $title)) {
                return true;
            }

            // Calculate similarity percentage
            $similarity = similar_text($sentence, $title, $percent);
            if ($percent > 70) { // 70% similarity threshold
                return true;
            }
        }

        // Skip dates, navigation, or other unwanted patterns
        $unwantedPatterns = [
            '/^\d{1,2}\s+(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)/i',
            '/^also on this blog/i',
            '/^fast[- ]forward to/i',
            '/^\d{4}$/i',
            '/^[^a-z]*$/i', // Only numbers/symbols
        ];

        foreach ($unwantedPatterns as $pattern) {
            if (preg_match($pattern, $sentence)) {
                return true;
            }
        }

        return false;
    }
}
