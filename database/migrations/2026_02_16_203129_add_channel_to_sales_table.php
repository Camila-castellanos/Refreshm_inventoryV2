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
        if (! Schema::hasColumn('sales', 'channel')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->string('channel')->default('system')->after('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('sales', 'channel')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn('channel');
            });
        }
    }
};
