<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('event', 32);
            $table->unsignedInteger('points');
            $table->boolean('enabled');
            $table->unsignedTinyInteger('minimum_score_percent')->nullable();
            $table->boolean('allow_retries')->default(false);
            $table->unsignedSmallInteger('streak_days')->nullable();
            $table->timestamps();
            $table->index(['event', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_rules');
    }
};
