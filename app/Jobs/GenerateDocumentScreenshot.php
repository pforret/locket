<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;

class GenerateDocumentScreenshot implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $backoff = 180;

    public int $timeout = 120;

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
            $screenshotPath = $this->generateScreenshot();

            if ($screenshotPath) {
                $this->document->update([
                    'screenshot' => $screenshotPath,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to generate screenshot', [
                'document_id' => $this->document->id,
                'url' => $this->document->url,
                'error' => $e->getMessage(),
            ]);

            // Don't throw exception for screenshot failures - they're not critical
        }
    }

    private function generateScreenshot(): ?string
    {
        try {
            $filename = 'screenshots/'.$this->document->id.'_'.time().'.png';
            $fullPath = storage_path('app/public/'.$filename);

            // Ensure screenshots directory exists
            $dir = dirname($fullPath);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            Browsershot::url($this->document->url)
                ->setOption('args', [
                    '--no-sandbox',
                    '--disable-setuid-sandbox',
                    '--disable-dev-shm-usage',
                    '--disable-gpu',
                    '--no-first-run',
                    '--disable-extensions',
                    '--disable-plugins',
                ])
                ->windowSize(1200, 800)
                ->fit(\Spatie\Browsershot\Manipulations::FIT_CONTAIN, 1200, 800)
                ->timeout(60)
                ->userAgent('Mozilla/5.0 (compatible; Locket/1.0; +https://locket.example.com)')
                ->dismissDialogs()
                ->waitUntilNetworkIdle()
                ->save($fullPath);

            return Storage::url($filename);
        } catch (\Exception $e) {
            Log::error('Screenshot generation failed', [
                'document_id' => $this->document->id,
                'url' => $this->document->url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
