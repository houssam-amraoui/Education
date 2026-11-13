<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exercise_attempts', function (Blueprint $table): void {
            // An explicit default prevents older servers from adding ON UPDATE.
            $table->timestamp('started_at')->useCurrent()->change();
        });
    }

    public function down(): void
    {
        Schema::table('exercise_attempts', function (Blueprint $table): void {
            $table->timestamp('started_at')->change();
        });
    }
};
