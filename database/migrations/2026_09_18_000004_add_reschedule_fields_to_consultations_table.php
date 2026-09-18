<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dateTime('suggested_scheduled_at')->nullable()->after('scheduled_at');
            $table->text('reschedule_note')->nullable()->after('suggested_scheduled_at');
            $table->integer('credits_deducted')->default(0)->after('fee');
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn(['suggested_scheduled_at', 'reschedule_note', 'credits_deducted']);
        });
    }
};
