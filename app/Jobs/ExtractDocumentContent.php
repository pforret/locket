<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Pforret\PfArticleExtractor\ArticleExtractor;

class ExtractDocumentContent implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $backoff = 120;

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
        try {
            $content = $this->extractContent();

            if ($content) {
                $this->document->update([
                    'content' => $content,
                ]);

                // Dispatch summary generation after content extraction
                GenerateDocumentSummary::dispatch($this->document);
            }
        } catch (\Exception $e) {
            Log::error('Failed to extract document content', [
                'document_id' => $this->document->id,
                'url' => $this->document->url,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function extractContent(): ?string
    {
        $url = $this->document->url;

        try {
            // First fetch the HTML content
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; Locket/1.0; +https://locket.example.com)',
                ])
                ->get($url);

            if (! $response->successful()) {
                throw new \Exception("Failed to fetch URL: HTTP {$response->status()}");
            }

            $html = $response->body();

            // Use pf-article-extractor to extract content
            $articleData = ArticleExtractor::getArticle($html);

            if ($articleData && $articleData->content && ! empty($articleData->content)) {
                return $articleData->content;
            }

            return null;
        } catch (\Exception $e) {
            Log::warning('Article extraction failed', [
                'document_id' => $this->document->id,
                'url' => $this->document->url,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
