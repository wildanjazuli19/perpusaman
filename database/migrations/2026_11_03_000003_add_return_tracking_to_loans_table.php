<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->unsignedInteger('quantity_returned')->default(0)->after('quantity');
        });

        // Sinkronkan transaksi lama yang sudah berstatus returned dari Tahap 2.
        DB::table('loans')->where('status', 'returned')->update([
            'quantity_returned' => DB::raw('quantity'),
        ]);
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn('quantity_returned');
        });
    }
};
