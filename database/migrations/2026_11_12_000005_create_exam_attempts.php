<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('deadline_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('expired_at')->nullable();
            $table->unsignedBigInteger('possible_points');
            $table->unsignedBigInteger('awarded_points')->nullable();
            $table->index(['exam_id', 'student_id', 'id']);
        });

        Schema::create('exam_attempt_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_attempt_id')->constrained()->cascadeOnDelete();
            // Keep the snapshot even if the source question is deleted.
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
            $table->unique(['exam_attempt_id', 'position']);
            $table->unique(['exam_attempt_id', 'source_question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempt_items');
        Schema::dropIfExists('exam_attempts');
    }
};
