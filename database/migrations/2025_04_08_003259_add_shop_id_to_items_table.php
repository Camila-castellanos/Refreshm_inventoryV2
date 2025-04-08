<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('items', function (Blueprint $table) {
            // Only add the shop_id column and constraint. DO NOT touch user_id here.
            if (!Schema::hasColumn('items', 'shop_id')) {
                $table->foreignId('shop_id')
                      ->nullable() // Nullable is crucial for existing data & gradual migration
                      ->after('user_id') // Place it after user_id logically
                      ->constrained('shops') // Links to shops table
                      ->onDelete('set null'); // Decide: SET NULL/CASCADE/RESTRICT. SET NULL might be safest for now.
                      // If a shop is deleted, the item loses its shop link but keeps its user link.
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            // Only drop the shop_id column and constraint.
            if (Schema::hasColumn('items', 'shop_id')) {
                try { $table->dropForeign(['shop_id']); } // Default name: items_shop_id_foreign
                catch (\Exception $e) { Log::warning("FK drop failed items.shop_id: ".$e->getMessage()); }
                $table->dropColumn('shop_id');
            }
        });
    }
};
