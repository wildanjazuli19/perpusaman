<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value');
            $table->timestamps();
        });

        // If this patch is installed on an existing database with an Admin,
        // preserve the closed-registration state from the moment of migration.
        $hasExistingAccount = Schema::hasTable('users') && DB::table('users')->exists();

        DB::table('app_settings')->insert([
            'key' => 'registration_locked',
            'value' => $hasExistingAccount ? '1' : '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
