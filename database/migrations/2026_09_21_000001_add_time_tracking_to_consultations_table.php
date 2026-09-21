<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->unsignedInteger('time_consumed_seconds')->default(0)->after('duration_minutes');
            $table->timestamp('doctor_joined_at')->nullable()->after('time_consumed_seconds');
            $table->timestamp('doctor_last_seen_at')->nullable()->after('doctor_joined_at');
            $table->timestamp('client_joined_at')->nullable()->after('doctor_last_seen_at');
            $table->timestamp('client_last_seen_at')->nullable()->after('client_joined_at');
            $table->timestamp('last_deducted_at')->nullable()->after('client_last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn([
                'time_consumed_seconds',
                'doctor_joined_at',
                'doctor_last_seen_at',
                'client_joined_at',
                'client_last_seen_at',
                'last_deducted_at',
            ]);
        });
    }
};
