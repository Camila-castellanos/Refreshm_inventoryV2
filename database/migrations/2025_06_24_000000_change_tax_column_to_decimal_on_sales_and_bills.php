<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Ensure doctrine/dbal is installed for column modifications
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('tax', 5, 2)->change();
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->decimal('tax', 5, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert columns back to float
        Schema::table('sales', function (Blueprint $table) {
            $table->float('tax')->change();
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->float('tax')->change();
        });
    }
};
