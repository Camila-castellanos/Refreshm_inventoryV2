<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
     public function up(): void
    {

        // Paso 2: Renombrar id -> old_id
        Schema::table('customers', function (Blueprint $table) {
            $table->renameColumn('id', 'old_id');
        });

        // Paso 3: Agregar nueva columna id con auto_increment y establecer como primary
        Schema::table('customers', function (Blueprint $table) {
            $table->bigIncrements('id')->first(); // o increments('id') si prefieres INT
        });
    }

    public function down(): void
    {
        // Revertir cambios
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->renameColumn('old_id', 'id');
        });

    }
};
