<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->bigInteger('amount');
            $table->string('source_key', 100);
            $table->timestamp('recorded_at');
            $table->unique(['student_id', 'source_key']);
        });

        foreach (['UPDATE', 'DELETE'] as $operation) {
            $trigger = 'point_transactions_block_'.strtolower($operation);
            DB::unprepared("CREATE TRIGGER {$trigger} BEFORE {$operation} ON point_transactions
                FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Point transactions are immutable'");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
    }
};
