<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('storages', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('user_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // Actualizar los registros existentes para establecer company_id basado en user_id
        DB::statement('
            UPDATE storages s
            JOIN users u ON s.user_id = u.id
            SET s.company_id = u.company_id
            WHERE u.company_id IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('storages', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
