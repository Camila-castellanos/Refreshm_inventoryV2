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
        // 1. Add the company_id column to the customers table
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('user_id');
            $table->index('company_id'); // Add index for better performance
        });

        // 2. Update existing records based on user_id
        DB::statement('
            UPDATE customers 
            SET company_id = (
            SELECT company_id 
            FROM users 
            WHERE users.id = customers.user_id
            AND users.company_id IS NOT NULL
            )
            WHERE user_id IS NOT NULL
        ');

        // 3. Add the foreign key constraint after updating the data
        Schema::table('customers', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropIndex(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
