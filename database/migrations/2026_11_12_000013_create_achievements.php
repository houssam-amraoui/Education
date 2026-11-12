<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('event', 32);
            $table->unsignedSmallInteger('required_count');
            $table->unsignedTinyInteger('minimum_score_percent')->nullable();
            $table->unsignedInteger('reward_points')->default(0);
            $table->boolean('enabled');
            $table->timestamps();
            $table->index(['event', 'enabled']);
        });

        Schema::create('student_achievements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('achievement_id')->constrained()->restrictOnDelete();
            $table->string('source_key', 100);
            $table->timestamp('awarded_at');
            $table->unique(['student_id', 'achievement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_achievements');
        Schema::dropIfExists('achievements');
    }
};
