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
        Schema::table('incoming_requests', function (Blueprint $table) {
            // Asumiendo que puede ser BIGINT
            $table->unsignedBigInteger('customer_id')->nullable()->after('id');
            $table->index('customer_id');
            // SIN constraint de foreign key para evitar problemas de compatibilidad en MySQL
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incoming_requests', function (Blueprint $table) {
            $table->dropIndex(['customer_id']);
            $table->dropColumn('customer_id');
        });
    }
};
