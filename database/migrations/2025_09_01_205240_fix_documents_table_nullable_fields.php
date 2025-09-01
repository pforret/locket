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
        Schema::table('documents', function (Blueprint $table) {
            // Make fields nullable that should be optional
            $table->string('title')->nullable()->change();
            $table->string('author')->nullable()->change();
            $table->string('source')->nullable()->change();
            $table->text('summary')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Revert to original constraints
            $table->string('title')->nullable(false)->change();
            $table->string('author')->nullable(false)->change();
            $table->string('source')->default('web')->nullable(false)->change();
            $table->text('summary')->nullable(false)->change();
        });
    }
};
