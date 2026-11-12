<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 12)->nullable();
            $table->unsignedSmallInteger('daily_study_goal_minutes')->nullable();
            $table->string('learning_focus')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['locale', 'daily_study_goal_minutes', 'learning_focus']);
        });
    }
};
