<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('paid')->default(1);
            
            $table->string('amount_paid')->nullable();
            $table->string('balance_remaining')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_account')->nullable();
            $table->text('notes')->nullable();
            
            $table->date('date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('credit');
            $table->dropConstrainedForeignId('tax_id');
            $table->dropColumn('paid');
            $table->dropColumn('amount_paid');
            $table->dropColumn('balance_remaining');
            $table->dropColumn('payment_method');
            $table->dropColumn('payment_account');
            $table->dropColumn('notes');
            $table->dropColumn('date');
        });
    }
};
