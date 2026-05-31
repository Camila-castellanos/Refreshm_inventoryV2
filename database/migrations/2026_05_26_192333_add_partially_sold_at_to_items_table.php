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
            $table->datetime('partially_sold_at')->nullable()->index();
        });

        // Backfill partially_sold_at for existing RESERVED items using their sale's created_at
        DB::statement("
            UPDATE items 
            SET partially_sold_at = (
                SELECT sales.created_at 
                FROM sales 
                WHERE sales.id = items.sale_id
                LIMIT 1
            )
            WHERE status = 'reserved' 
              AND sale_id IS NOT NULL 
              AND partially_sold_at IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('partially_sold_at');
        });
    }
};
