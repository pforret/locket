<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->timestamp('published_at')->nullable()->index(); // when the document was published
            $table->string("title")->unique();      // title of the document
            $table->string("url")->unique();        // original URL
            $table->string("image")->nullable();    // main image from original content, cached locally here
            $table->string("screenshot")->nullable();   // screenshot of the page, cached locally here
            $table->string("author")->index();      // author of the document: name/email
            $table->string("source")->default("web");   // source of the document: web, email, mobile, ...
            $table->text("summary");                // short summary of the document
            $table->text("content")->nullable();    // full content of the document, if applicable
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
