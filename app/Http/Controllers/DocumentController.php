<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Jobs\ProcessDocumentMetadata;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    public function index(): Response
    {
        $documents = Document::with('tags')
            ->latest()
            ->paginate(20);

        return Inertia::render('Documents/Index', [
            'documents' => $documents,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Documents/Create');
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $document = Document::create($request->validated());

        if ($request->has('tags')) {
            $document->syncTags($request->input('tags'));
        }

        // Dispatch background processing jobs
        ProcessDocumentMetadata::dispatch($document);

        return redirect()->route('documents.index')
            ->with('message', 'Document saved successfully! Processing in background...');
    }

    public function show(Document $document): Response
    {
        $document->load('tags');

        return Inertia::render('Documents/Show', [
            'document' => $document,
        ]);
    }

    public function update(Request $request, Document $document): RedirectResponse
    {
        $request->validate([
            'tags' => 'array',
            'tags.*' => 'string|max:255',
        ]);

        if ($request->has('tags')) {
            $document->syncTags($request->input('tags'));
        }

        return redirect()->back()
            ->with('message', 'Tags updated successfully!');
    }
}
