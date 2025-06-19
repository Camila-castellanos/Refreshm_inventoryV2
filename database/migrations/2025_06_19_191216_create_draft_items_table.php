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
        Schema::create('draft_items', function (Blueprint $table) {
            $table->id();
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('type')->nullable();
            $table->string('colour')->nullable();
            $table->string('battery')->nullable();
            $table->string('grade')->nullable();
            $table->text('issues')->nullable();
            $table->string('imei')->nullable();
            $table->string('location')->nullable();
            $table->integer('storage_position')->nullable();
            $table->date('date')->nullable();
            $table->decimal('subtotal', 10, 2)->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->decimal('selling_price', 10, 2)->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreignId('draft_id')
                ->constrained('drafts')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId(('vendor_id'))
                ->nullable()
                ->constrained('vendors')
                ->onDelete('set null')
                ->onUpdate('cascade');
            $table->foreignId('tax_id')
                ->nullable()
                ->constrained('taxes')
                ->onDelete('set null')
                ->onUpdate('cascade');
            $table->foreignId('storage_id')
                ->nullable()
                ->constrained('storages')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draft_items');
    }
};
