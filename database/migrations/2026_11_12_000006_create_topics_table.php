<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamps();
            $table->index(['status', 'title']);
        });

        foreach (['exam', 'exercise', 'lesson', 'resource', 'video'] as $content) {
            Schema::create($this->pivot($content), function (Blueprint $table) use ($content): void {
                $foreignKey = $content.'_id';
                $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
                $table->foreignId($foreignKey)->constrained()->cascadeOnDelete();
                $table->primary(['topic_id', $foreignKey]);
            });
        }
    }

    public function down(): void
    {
        foreach (['exam', 'exercise', 'lesson', 'resource', 'video'] as $content) {
            Schema::dropIfExists($this->pivot($content));
        }

        Schema::dropIfExists('topics');
    }

    private function pivot(string $content): string
    {
        return $content === 'video' ? 'topic_video' : $content.'_topic';
    }
};
