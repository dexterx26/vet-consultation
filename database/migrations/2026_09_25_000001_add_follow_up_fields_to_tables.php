<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vet_profiles', function (Blueprint $table) {
            $table->decimal('follow_up_fee', 8, 2)->nullable()->default(0.00)->after('consultation_fee');
        });

        Schema::table('consultations', function (Blueprint $table) {
            $table->boolean('is_follow_up')->default(false)->after('credits_cost');
            $table->foreignId('parent_consultation_id')->nullable()->constrained('consultations')->nullOnDelete()->after('is_follow_up');
        });

        Schema::table('consultation_records', function (Blueprint $table) {
            $table->dateTime('follow_up_date')->nullable()->after('follow_up_instructions');
            $table->decimal('follow_up_fee', 8, 2)->nullable()->default(0.00)->after('follow_up_date');
            $table->foreignId('follow_up_consultation_id')->nullable()->constrained('consultations')->nullOnDelete()->after('follow_up_fee');
        });
    }

    public function down(): void
    {
        Schema::table('consultation_records', function (Blueprint $table) {
            $table->dropForeign(['follow_up_consultation_id']);
            $table->dropColumn(['follow_up_date', 'follow_up_fee', 'follow_up_consultation_id']);
        });

        Schema::table('consultations', function (Blueprint $table) {
            $table->dropForeign(['parent_consultation_id']);
            $table->dropColumn(['is_follow_up', 'parent_consultation_id']);
        });

        Schema::table('vet_profiles', function (Blueprint $table) {
            $table->dropColumn(['follow_up_fee']);
        });
    }
};
