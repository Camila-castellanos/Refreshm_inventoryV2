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
            $table->foreignIdFor(\App\Models\Storage::class)
            ->nullable()
            ->constrained()
            ->nullOnDelete();
            $table->unsignedInteger('position')->nullOnDelete();
            $table->unique(['storage_id', 'position'], 'items_storage_position_unique');
            $table->unsignedBigInteger('sold_position')->nullable();
            $table->unsignedBigInteger('sold_storage_id')->nullable();
            $table->string('sold_storage_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['storage_id']);
            $table->dropUnique('items_storage_position_unique');
            $table->dropColumn(['storage_id', 'position', 'sold_position', 'sold_storage_id', 'sold_storage_name']);
        });
    }
};
