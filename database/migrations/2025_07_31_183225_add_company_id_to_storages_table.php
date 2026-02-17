<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        // Use Laravel query builder for cross-database compatibility
        $storages = DB::table('storages')->get();
        foreach ($storages as $storage) {
            if ($storage->user_id) {
                $user = DB::table('users')->where('id', $storage->user_id)->first();
                if ($user && $user->company_id) {
                    DB::table('storages')
                        ->where('id', $storage->id)
                        ->update(['company_id' => $user->company_id]);
                }
            }
        }
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
