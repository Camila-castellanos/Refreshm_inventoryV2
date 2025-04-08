<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up() {
        Schema::table('users', function (Blueprint $table) {
             if (!Schema::hasColumn('users', 'company_id')) {
                $table->foreignId('company_id')
                      ->nullable() // Nullable for migration
                      ->after('id')
                      ->constrained('companies')
                      ->onDelete('set null'); // Or restrict
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down() {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'company_id')) {
                try { $table->dropForeign(['company_id']); }
                catch (\Exception $e) { Log::warning("FK drop failed users.company_id: ".$e->getMessage()); }
                $table->dropColumn('company_id');
            }
        });
    }
};
