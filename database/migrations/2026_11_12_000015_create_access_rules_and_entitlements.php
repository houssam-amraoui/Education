<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('target_type', 24);
            $table->unsignedBigInteger('target_id');
            $table->string('requirement', 24);
            $table->foreignId('required_level_id')->nullable()->constrained('levels')->restrictOnDelete();
            $table->foreignId('required_lesson_id')->nullable()->constrained('lessons')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['target_type', 'target_id', 'requirement']);
        });

        Schema::create('entitlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->string('target_type', 24);
            $table->unsignedBigInteger('target_id');
            $table->string('source', 24);
            $table->foreignId('point_unlock_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->timestamp('granted_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->unique(['student_id', 'target_type', 'target_id', 'source']);
        });

        DB::table('entitlements')->insertUsing(
            ['student_id', 'target_type', 'target_id', 'source', 'point_unlock_id', 'granted_at'],
            DB::table('point_unlocks')->join('point_costs', 'point_costs.id', '=', 'point_unlocks.point_cost_id')
                ->select('point_unlocks.student_id', 'point_costs.target_type', 'point_costs.target_id')
                ->selectRaw("'points', point_unlocks.id, point_unlocks.unlocked_at"),
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('entitlements');
        Schema::dropIfExists('access_rules');
    }
};
