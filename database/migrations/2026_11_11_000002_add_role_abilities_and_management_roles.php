<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_abilities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->string('ability');
            $table->unique(['role_id', 'ability']);
        });

        $now = now();
        $teacher = DB::table('roles')->insertGetId([
            'key' => 'teacher', 'name' => 'Teacher', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $administrator = DB::table('roles')->insertGetId([
            'key' => 'administrator', 'name' => 'Administrator', 'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('role_abilities')->insert([
            ['role_id' => $teacher, 'ability' => 'admin.enter'],
            ['role_id' => $administrator, 'ability' => 'admin.enter'],
            ['role_id' => $administrator, 'ability' => 'users.manage'],
            ['role_id' => $administrator, 'ability' => 'roles.manage'],
            ['role_id' => $administrator, 'ability' => 'roles.assign'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('role_abilities');
        DB::table('roles')->whereIn('key', ['teacher', 'administrator'])->delete();
    }
};
