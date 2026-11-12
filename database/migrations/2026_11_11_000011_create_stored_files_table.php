<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stored_files', function (Blueprint $table): void {
            $table->id();
            $table->string('disk', 32);
            $table->string('path', 160);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->timestamps();
            $table->unique(['disk', 'path']);
        });

        Schema::table('resources', function (Blueprint $table): void {
            $table->foreignId('stored_file_id')->nullable()->unique()->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('resources', function (Blueprint $table): void {
            $table->dropForeign(['stored_file_id']);
            $table->dropUnique(['stored_file_id']);
            $table->dropColumn('stored_file_id');
        });

        Schema::dropIfExists('stored_files');
    }
};
