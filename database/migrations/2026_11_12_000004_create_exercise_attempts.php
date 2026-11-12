<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercise_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exercise_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('possible_points');
            $table->unsignedBigInteger('awarded_points')->nullable();
            $table->index(['student_id', 'exercise_id', 'submitted_at', 'id'], 'exercise_attempt_history');
        });

        Schema::create('exercise_attempt_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exercise_attempt_id')->constrained()->cascadeOnDelete();
            // Authored questions can be removed without invalidating the snapshot.
            $table->unsignedBigInteger('source_question_id');
            $table->unsignedInteger('position');
            $table->string('type');
            $table->text('prompt');
            $table->json('content');
            $table->json('answer_key');
            $table->text('explanation')->nullable();
            $table->unsignedSmallInteger('points');
            $table->json('submitted_answer')->nullable();
            $table->boolean('correct')->nullable();
            $table->unsignedSmallInteger('awarded_points')->nullable();
            $table->unique(['exercise_attempt_id', 'position']);
            $table->unique(['exercise_attempt_id', 'source_question_id'], 'exercise_attempt_source_question');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_attempt_items');
        Schema::dropIfExists('exercise_attempts');
    }
};
