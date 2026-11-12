<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('level_user', function (Blueprint $table): void {
            $table->foreignId('level_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'level_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('level_user');
    }
};
