<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_costs', function (Blueprint $table): void {
            $table->id();
            $table->string('target_type', 24);
            $table->unsignedBigInteger('target_id');
            $table->unsignedInteger('points');
            $table->boolean('enabled');
            $table->timestamps();
            $table->unique(['target_type', 'target_id']);
        });

        Schema::create('point_unlocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('point_cost_id')->constrained()->restrictOnDelete();
            $table->foreignId('point_transaction_id')->unique()->constrained()->restrictOnDelete();
            $table->timestamp('unlocked_at');
            $table->unique(['student_id', 'point_cost_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_unlocks');
        Schema::dropIfExists('point_costs');
    }
};
