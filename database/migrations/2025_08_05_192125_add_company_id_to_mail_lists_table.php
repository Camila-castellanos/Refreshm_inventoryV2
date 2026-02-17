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
        Schema::table('mail_lists', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('user_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // Actualizar registros existentes para establecer company_id basado en user_id
        $mailLists = DB::table('mail_lists')->get();
        foreach ($mailLists as $mailList) {
            if ($mailList->user_id) {
                $user = DB::table('users')->where('id', $mailList->user_id)->first();
                if ($user && $user->company_id) {
                    DB::table('mail_lists')
                        ->where('id', $mailList->id)
                        ->update(['company_id' => $user->company_id]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mail_lists', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
