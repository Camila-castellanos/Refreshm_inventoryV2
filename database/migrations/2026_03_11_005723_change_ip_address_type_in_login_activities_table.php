<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('login_activities', function (Blueprint $table) {
            $table->text('ip_address')->nullable()->change();
        });

        $activities = DB::table('login_activities')->get();

        foreach ($activities as $activity) {
            if (! empty($activity->ip_address)) {
                $plainIp = $this->decryptIp($activity->ip_address) ?? $activity->ip_address;
                $plainIp = $this->normalizeIp($plainIp);

                DB::table('login_activities')
                    ->where('id', $activity->id)
                    ->update(['ip_address' => Crypt::encrypt($plainIp)]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $activities = DB::table('login_activities')->get();

        foreach ($activities as $activity) {
            if (! empty($activity->ip_address)) {
                $decryptedIp = $this->decryptIp($activity->ip_address);

                if ($decryptedIp === null) {
                    $decryptedIp = $activity->ip_address;
                }

                $decryptedIp = $this->normalizeIp($decryptedIp);

                DB::table('login_activities')
                    ->where('id', $activity->id)
                    ->update(['ip_address' => substr($decryptedIp, 0, 45)]);
            }
        }

        Schema::table('login_activities', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->change();
        });
    }

    private function decryptIp(string $value): ?string
    {
        try {
            return Crypt::decrypt($value);
        } catch (\Throwable $e) {
            try {
                return Crypt::decryptString($value);
            } catch (\Throwable $e) {
                return null;
            }
        }
    }

    private function normalizeIp(string $value): string
    {
        $unserialized = @unserialize($value);

        if ($unserialized !== false && is_string($unserialized)) {
            return $unserialized;
        }

        if ($value === 'b:0;') {
            return '0';
        }

        return $value;
    }
};
