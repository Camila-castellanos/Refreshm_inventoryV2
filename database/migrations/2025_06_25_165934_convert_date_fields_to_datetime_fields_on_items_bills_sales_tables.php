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
        // Convertir campo 'date' a 'datetime' en la tabla sales
        Schema::table('sales', function (Blueprint $table) {
            $table->dateTime('date')->nullable()->change();
        });

        // Convertir campo 'date' a 'datetime' en la tabla bills
        Schema::table('bills', function (Blueprint $table) {
            $table->dateTime('date')->nullable()->change();
        });

        // Convertir campo 'date' a 'datetime' en la tabla items
        Schema::table('items', function (Blueprint $table) {
            $table->dateTime('date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir campo 'datetime' a 'date' en la tabla sales
        Schema::table('sales', function (Blueprint $table) {
            $table->date('date')->nullable()->change();
        });

        // Revertir campo 'datetime' a 'date' en la tabla bills
        Schema::table('bills', function (Blueprint $table) {
            $table->date('date')->nullable()->change();
        });

        // Revertir campo 'datetime' a 'date' en la tabla items
        Schema::table('items', function (Blueprint $table) {
            $table->date('date')->nullable()->change();
        });
    }
};
