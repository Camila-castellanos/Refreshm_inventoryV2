<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('storages', function (Blueprint $table) {
        $table->unsignedBigInteger('user_id')->nullable()->after('limit'); // Agregar columna user_id
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade'); // Clave foránea
    });
    // Asignar user_id a registros existentes
    $user = \App\Models\User::where('email', 'will@refreshmobile.ca')->first();
    if ($user) {
        \Illuminate\Support\Facades\DB::table('storages')->update(['user_id' => $user->id]);
    }
}

public function down()
{
    Schema::table('storages', function (Blueprint $table) {
        $table->dropForeign(['user_id']); // Eliminar clave foránea
        $table->dropColumn('user_id'); // Eliminar columna
    });
}
};
