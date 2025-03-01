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
            $table->foreignId('tax_id')->nullable();
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
            if (Schema::hasColumn('sales', 'credit')) {
                $table->dropColumn('credit');
            }
            if (Schema::hasColumn('sales', 'tax_id')) {
                $table->dropColumn('tax_id');
            }
            if (Schema::hasColumn('sales', 'paid')) {
                $table->dropColumn('paid');
            }
            if (Schema::hasColumn('sales', 'amount_paid')) {
                $table->dropColumn('amount_paid');
            }
            if (Schema::hasColumn('sales', 'balance_remaining')) {
                $table->dropColumn('balance_remaining');
            }
            if (Schema::hasColumn('sales', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
            if (Schema::hasColumn('sales', 'payment_account')) {
                $table->dropColumn('payment_account');
            }
            if (Schema::hasColumn('sales', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('sales', 'date')) {
                $table->dropColumn('date');
            }
        });
    }
};
