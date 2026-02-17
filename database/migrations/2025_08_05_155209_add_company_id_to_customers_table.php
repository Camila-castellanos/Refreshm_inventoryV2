<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        // 2. Update existing records based on user_id (cross-database compatible)
        $customers = DB::table('customers')->get();
        foreach ($customers as $customer) {
            if ($customer->user_id) {
                $user = DB::table('users')->where('id', $customer->user_id)->first();
                if ($user && $user->company_id) {
                    DB::table('customers')
                        ->where('id', $customer->id)
                        ->update(['company_id' => $user->company_id]);
                }
            }
        }

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
