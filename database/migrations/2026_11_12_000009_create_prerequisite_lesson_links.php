<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['lesson', 'exercise', 'exam'] as $content) {
            Schema::create($content.'_prerequisite_lesson', function (Blueprint $table) use ($content): void {
                $source = $content.'_id';
                $table->foreignId($source)->constrained()->cascadeOnDelete();
                $table->foreignId('prerequisite_lesson_id')->constrained('lessons')->cascadeOnDelete();
                $table->primary([$source, 'prerequisite_lesson_id']);
            });
        }
    }

    public function down(): void
    {
        foreach (['exam', 'exercise', 'lesson'] as $content) {
            Schema::dropIfExists($content.'_prerequisite_lesson');
        }
    }
};
