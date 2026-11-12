<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('level_resource', function (Blueprint $table): void {
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->foreignId('level_id')->constrained()->cascadeOnDelete();
            $table->primary(['resource_id', 'level_id']);
            $table->index(['level_id', 'resource_id']);
        });

        Schema::create('lesson_resource', function (Blueprint $table): void {
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->primary(['resource_id', 'lesson_id']);
            $table->index(['lesson_id', 'resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_resource');
        Schema::dropIfExists('level_resource');
    }
};
