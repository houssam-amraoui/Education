<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table): void {
            $table->unique(['id', 'type']);
        });

        Schema::create('questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exercise_id')->nullable()->constrained();
            $table->foreignId('exam_id')->nullable();
            // Match the parent enum's stored values for the composite foreign key.
            $table->enum('exam_type', ['document', 'interactive'])->default('interactive');
            $table->foreign(['exam_id', 'exam_type'])->references(['id', 'type'])->on('exams');
            $table->enum('type', [
                'single_choice', 'multiple_selection', 'true_false', 'matching',
                'fill_in_the_blanks', 'short_answer', 'ordering',
            ]);
            $table->text('prompt');
            $table->json('content');
            $table->json('answer');
            $table->unsignedSmallInteger('points');
            $table->text('explanation')->nullable();
            $table->unsignedInteger('position')->nullable();
            $table->timestamps();
            $table->unique(['exercise_id', 'position']);
            $table->unique(['exam_id', 'position']);
        });

        DB::statement('ALTER TABLE questions ADD CONSTRAINT questions_single_owner
            CHECK ((exercise_id IS NULL) <> (exam_id IS NULL) AND exam_type = \'interactive\')');
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
        Schema::table('exams', function (Blueprint $table): void {
            $table->dropUnique(['id', 'type']);
        });
    }
};
