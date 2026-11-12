<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->enum('type', ['document', 'interactive']);
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->enum('access', ['public', 'registered'])->default('public');
            $table->dateTime('available_from')->nullable();
            $table->dateTime('available_until')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->foreignId('exam_file_id')->nullable()->unique()->constrained('stored_files')->restrictOnDelete();
            $table->foreignId('correction_file_id')->nullable()->unique()->constrained('stored_files')->restrictOnDelete();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->unsignedInteger('position')->nullable();
            $table->timestamps();
            $table->unique(['unit_id', 'position']);
            $table->index(['status', 'unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
