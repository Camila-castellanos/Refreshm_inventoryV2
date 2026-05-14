<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->enum('status', ['available', 'reserved', 'sold'])->default('available')->index();
        });

        // Backfill existing data
        \Illuminate\Support\Facades\DB::table('items')->whereNotNull('sold')->update(['status' => 'sold']);
        \Illuminate\Support\Facades\DB::table('items')->whereNull('sold')->whereNotNull('sale_id')->update(['status' => 'reserved']);
        \Illuminate\Support\Facades\DB::table('items')->whereNull('sold')->whereNull('sale_id')->update(['status' => 'available']);
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
