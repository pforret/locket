<?php

namespace App\Console\Commands;

use App\Jobs\ProcessDocumentMetadata;
use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class AddUrlCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'document:add {url?} {--title=} {--tags=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a new URL to the document collection';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $url = $this->argument('url');
        $title = $this->option('title');
        $tagsInput = $this->option('tags');

        // Get URL if not provided
        if (! $url) {
            $url = text(
                label: 'Enter the URL to save:',
                placeholder: 'https://example.com/article',
                required: true,
                validate: fn (string $value) => match (true) {
                    ! filter_var($value, FILTER_VALIDATE_URL) => 'Please enter a valid URL.',
                    default => null
                }
            );
        }

        // Validate URL
        $validator = Validator::make(['url' => $url], [
            'url' => ['required', 'url', 'max:2048'],
        ]);

        if ($validator->fails()) {
            $this->error('Invalid URL: '.$validator->errors()->first('url'));

            return self::FAILURE;
        }

        // Check if URL already exists
        if (Document::where('url', $url)->exists()) {
            $this->error('This URL has already been saved.');

            return self::FAILURE;
        }

        // Get title if not provided
        if (! $title) {
            $title = text(
                label: 'Enter a title (optional):',
                placeholder: 'Leave empty to auto-extract from URL',
            );
        }

        // Get tags if not provided
        if (! $tagsInput) {
            $tagsInput = text(
                label: 'Enter tags (optional):',
                placeholder: 'javascript, tutorial, programming',
            );
        }

        // Process tags
        $tags = [];
        if ($tagsInput) {
            $tags = array_map('trim', explode(',', $tagsInput));
            $tags = array_filter($tags, fn ($tag) => ! empty($tag));
        }

        // Show summary and confirm
        $this->info('Ready to save:');
        $this->table(
            ['Field', 'Value'],
            [
                ['URL', $url],
                ['Title', $title ?: '(will be auto-extracted)'],
                ['Tags', empty($tags) ? '(none)' : implode(', ', $tags)],
            ]
        );

        if (! confirm('Save this URL?', default: true)) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        // Create document
        try {
            $document = Document::create([
                'url' => $url,
                'title' => $title ?: null,
            ]);

            // Add tags
            if (! empty($tags)) {
                $document->syncTags($tags);
            }

            // Dispatch background processing jobs
            ProcessDocumentMetadata::dispatch($document);

            $this->info("✅ URL saved successfully with ID: {$document->id}");
            $this->info('🔄 Content extraction will happen in the background.');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to save URL: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
