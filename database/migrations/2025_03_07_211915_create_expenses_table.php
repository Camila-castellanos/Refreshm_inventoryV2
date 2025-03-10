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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date("date")->nullable();
            $table->string("name")->nullable();
            $table->string("category")->nullable();
            $table->float("amount")->nullable();
            $table->foreignId("user_id")->nullable()->constrained();
            $table->timestamps();
            $table->decimal('tax', 5, 2)->default(0);
            $table->foreignId('tax_id')->nullable();
            $table->decimal('total', 10, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
