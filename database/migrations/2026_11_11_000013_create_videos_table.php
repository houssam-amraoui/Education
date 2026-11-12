<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('provider', 32);
            $table->string('provider_id');
            $table->enum('access', ['public', 'registered', 'level', 'prerequisite', 'points', 'premium'])->default('public');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->unsignedInteger('position')->nullable()->unique();
            $table->timestamps();
            $table->index(['status', 'access']);
        });

        Schema::create('lesson_video', function (Blueprint $table): void {
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('video_id')->constrained()->cascadeOnDelete();
            $table->primary(['lesson_id', 'video_id']);
            $table->index(['video_id', 'lesson_id']);
        });

        Schema::create('level_video', function (Blueprint $table): void {
            $table->foreignId('level_id')->constrained()->cascadeOnDelete();
            $table->foreignId('video_id')->constrained()->cascadeOnDelete();
            $table->primary(['level_id', 'video_id']);
            $table->index(['video_id', 'level_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('level_video');
        Schema::dropIfExists('lesson_video');
        Schema::dropIfExists('videos');
    }
};
