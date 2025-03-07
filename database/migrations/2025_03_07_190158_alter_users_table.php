<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId("store_id")->nullable()->constrained();
            $table->foreignId("location_id")->nullable()->constrained();
            $table->string('invoice')->nullable();
            $table->string('column_headers')->nullable();
            $table->string('sold_headers')->nullable();
            $table->enum('role', ['OWNER', 'ADMIN', 'USER'])->default('USER');
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_store_id_foreign');
            $table->dropForeign('users_location_id_foreign');
            $table->dropColumn('store_id');
            $table->dropColumn('location_id');
            $table->dropColumn('invoice');
            $table->dropColumn('column_headers');
            $table->dropColumn('sold_headers');
            $table->dropColumn('role');
            $table->dropColumn('deleted_at');
        });
    }
};