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
        Schema::table('draft_items', function (Blueprint $table) {
            $table->boolean('draft_unassigned')->default(false)->after('selling_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('draft_items', function (Blueprint $table) {
            $table->dropColumn('draft_unassigned');
        });
    }
};
