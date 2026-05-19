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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('password', 255)->nullable()->after('email');
            $table->timestamp('email_verified_at')->nullable()->after('password');
            $table->string('remember_token', 100)->nullable()->after('email_verified_at');
            $table->timestamp('magic_link_expires_at')->nullable()->after('remember_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'password',
                'email_verified_at',
                'remember_token',
                'magic_link_expires_at',
            ]);
        });
    }
};