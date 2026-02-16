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
        if (! Schema::hasColumn('sales', 'market_id')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->foreignId('market_id')
                    ->nullable()
                    ->constrained('markets')
                    ->onDelete('set null')
                    ->after('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('sales', 'market_id')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropForeign(['market_id']);
                $table->dropColumn('market_id');
            });
        }
    }
};
