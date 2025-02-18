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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string("vendor");
            $table->foreignId('user_id')->constrained();
            $table->text('first_name')->nullable();
            $table->text('last_name')->nullable();
            $table->text('email')->nullable();
            $table->text('phone')->nullable();
            $table->text('phone_optional')->nullable();
            $table->string('website')->nullable();
            $table->string('notes')->nullable();
            $table->string('currency')->nullable();
            $table->text('address')->nullable();
            $table->text('address_optional')->nullable();
            $table->text('address_country')->nullable();
            $table->text('address_state')->nullable();
            $table->text('address_city')->nullable();
            $table->text('address_postal')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
