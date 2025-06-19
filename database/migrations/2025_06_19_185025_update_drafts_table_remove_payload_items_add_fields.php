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
        Schema::table('drafts', function (Blueprint $table) {
            
            $table->dropColumn(['payload', 'type']);
            $table->string('vendor')->nullable()->after('user_id');
            $table->date('date')->nullable()->after('vendor');
            $table->string('title')->after('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drafts', function (Blueprint $table) {
            $table->dropColumn(['vendor', 'date', 'title']);
            $table->string('type')->after('user_id');
            $table->json('payload')->after('type');
        });
    }
};
