<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->string('subject', 160);
            $table->string('source_type', 32)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('source_title')->nullable();
            $table->timestamp('last_message_at');
            $table->timestamps();
            $table->index(['student_id', 'last_message_at', 'id']);
            $table->index(['last_message_at', 'id']);
        });

        Schema::create('messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->restrictOnDelete();
            $table->foreignId('sender_id')->constrained('users')->restrictOnDelete();
            $table->boolean('from_teacher');
            $table->text('body');
            $table->timestamps();
            $table->index(['conversation_id', 'id']);
            $table->index(['conversation_id', 'from_teacher', 'id']);
        });

        $roles = DB::table('roles')->whereIn('key', ['teacher', 'administrator'])->pluck('id');
        DB::table('role_abilities')->insert($roles->map(fn ($id) => [
            'role_id' => $id, 'ability' => 'messages.manage',
        ])->all());
    }

    public function down(): void
    {
        DB::table('role_abilities')->where('ability', 'messages.manage')->delete();
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};
