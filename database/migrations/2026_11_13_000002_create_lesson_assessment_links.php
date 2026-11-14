<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercise_lesson', function (Blueprint $table): void {
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->primary(['lesson_id', 'exercise_id']);
        });

        Schema::create('exam_lesson', function (Blueprint $table): void {
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->primary(['lesson_id', 'exam_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_lesson');
        Schema::dropIfExists('exercise_lesson');
    }
};
