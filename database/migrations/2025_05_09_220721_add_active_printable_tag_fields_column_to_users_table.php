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
        Schema::table('users', function (Blueprint $table) {
            $table->json('printable_tag_fields')->nullable();
        });
        DB::table('users')
            ->whereNull('printable_tag_fields')
            ->update([
                'printable_tag_fields' => json_encode([
                    'manufacturer',
                    'model',
                    'storage',
                    'colour',
                    'battery',
                    'imei',
                ]),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('printable_tag_fields');
        });
    }
};
