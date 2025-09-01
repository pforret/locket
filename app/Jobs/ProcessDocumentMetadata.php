<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessDocumentMetadata implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

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
            $this->extractMetadata();

            // Dispatch follow-up jobs
            ExtractDocumentContent::dispatch($this->document);
            GenerateDocumentScreenshot::dispatch($this->document);
        } catch (\Exception $e) {
            Log::error('Failed to process document metadata', [
                'document_id' => $this->document->id,
                'url' => $this->document->url,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function extractMetadata(): void
    {
        $url = $this->document->url;

        // Fetch HTML content
        $response = Http::timeout(30)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; Locket/1.0; +https://locket.example.com)',
            ])
            ->get($url);

        if (! $response->successful()) {
            throw new \Exception("Failed to fetch URL: HTTP {$response->status()}");
        }

        $html = $response->body();
        $dom = new \DOMDocument;
        @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new \DOMXPath($dom);

        $updates = [];

        // Extract title if not already set
        if (empty($this->document->title)) {
            $title = $this->extractTitle($xpath, $dom);
            if ($title) {
                $updates['title'] = $title;
            }
        }

        // Extract author
        $author = $this->extractAuthor($xpath);
        if ($author) {
            $updates['author'] = $author;
        }

        // Extract published date
        $publishedAt = $this->extractPublishedDate($xpath);
        if ($publishedAt) {
            $updates['published_at'] = $publishedAt;
        }

        // Extract main image
        $image = $this->extractImage($xpath, $url);
        if ($image) {
            $updates['image'] = $image;
        }

        // Extract source/site name
        $source = $this->extractSource($xpath, $url);
        if ($source) {
            $updates['source'] = $source;
        }

        // Update document if we have changes
        if (! empty($updates)) {
            $this->document->update($updates);
        }
    }

    private function extractTitle(\DOMXPath $xpath, \DOMDocument $dom): ?string
    {
        // Try Open Graph title
        $nodes = $xpath->query('//meta[@property="og:title"]/@content');
        if ($nodes->length > 0) {
            return trim($nodes->item(0)->nodeValue);
        }

        // Try Twitter title
        $nodes = $xpath->query('//meta[@name="twitter:title"]/@content');
        if ($nodes->length > 0) {
            return trim($nodes->item(0)->nodeValue);
        }

        // Try regular title tag
        $nodes = $xpath->query('//title');
        if ($nodes->length > 0) {
            return trim($nodes->item(0)->nodeValue);
        }

        // Try h1
        $nodes = $xpath->query('//h1');
        if ($nodes->length > 0) {
            return trim($nodes->item(0)->nodeValue);
        }

        return null;
    }

    private function extractAuthor(\DOMXPath $xpath): ?string
    {
        // Try various author meta tags
        $selectors = [
            '//meta[@name="author"]/@content',
            '//meta[@property="article:author"]/@content',
            '//meta[@name="twitter:creator"]/@content',
            '//*[@class="author" or @class="byline" or @class="writer"]',
        ];

        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                $value = trim($nodes->item(0)->nodeValue);
                if (! empty($value)) {
                    return $value;
                }
            }
        }

        return null;
    }

    private function extractPublishedDate(\DOMXPath $xpath): ?string
    {
        // Try various date selectors
        $selectors = [
            '//meta[@property="article:published_time"]/@content',
            '//meta[@name="publishdate"]/@content',
            '//meta[@name="date"]/@content',
            '//time[@datetime]/@datetime',
            '//time[@pubdate]/@datetime',
        ];

        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                $value = trim($nodes->item(0)->nodeValue);
                if (! empty($value)) {
                    try {
                        return date('Y-m-d H:i:s', strtotime($value));
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
        }

        return null;
    }

    private function extractImage(\DOMXPath $xpath, string $baseUrl): ?string
    {
        // Try Open Graph image
        $nodes = $xpath->query('//meta[@property="og:image"]/@content');
        if ($nodes->length > 0) {
            return $this->resolveUrl($nodes->item(0)->nodeValue, $baseUrl);
        }

        // Try Twitter image
        $nodes = $xpath->query('//meta[@name="twitter:image"]/@content');
        if ($nodes->length > 0) {
            return $this->resolveUrl($nodes->item(0)->nodeValue, $baseUrl);
        }

        return null;
    }

    private function extractSource(\DOMXPath $xpath, string $url): ?string
    {
        // Try Open Graph site name
        $nodes = $xpath->query('//meta[@property="og:site_name"]/@content');
        if ($nodes->length > 0) {
            return trim($nodes->item(0)->nodeValue);
        }

        // Fall back to domain name
        $parsed = parse_url($url);

        return $parsed['host'] ?? null;
    }

    private function resolveUrl(string $url, string $baseUrl): string
    {
        if (parse_url($url, PHP_URL_SCHEME)) {
            return $url; // Already absolute
        }

        $base = parse_url($baseUrl);
        $scheme = $base['scheme'] ?? 'https';
        $host = $base['host'] ?? '';

        if (str_starts_with($url, '//')) {
            return $scheme.':'.$url;
        }

        if (str_starts_with($url, '/')) {
            return $scheme.'://'.$host.$url;
        }

        return $scheme.'://'.$host.'/'.ltrim($url, '/');
    }
}
