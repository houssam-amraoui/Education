<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event', 32);
            $table->char('actor_key', 64);
            $table->foreignId('student_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('target_id')->default(0);
            $table->date('event_day');
            $table->dateTime('occurred_at');
            $table->unique(['event', 'actor_key', 'target_id', 'event_day'], 'analytics_event_once_per_day');
            $table->index(['event', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
