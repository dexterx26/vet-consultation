<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vet_profiles', function (Blueprint $table) {
            $table->decimal('additional_pet_fee', 8, 2)->nullable()->default(250.00)->after('consultation_fee');
            $table->integer('additional_pet_duration')->nullable()->default(15)->after('additional_pet_fee');
        });

        Schema::table('consultations', function (Blueprint $table) {
            $table->integer('duration_minutes')->default(15)->after('fee');
            $table->decimal('base_fee', 8, 2)->default(0.00)->after('duration_minutes');
            $table->decimal('additional_fee', 8, 2)->default(0.00)->after('base_fee');
            $table->integer('credits_cost')->default(300)->after('credits_deducted');
        });

        Schema::create('consultation_pets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained('consultations')->onDelete('cascade');
            $table->foreignId('pet_id')->constrained('pets')->onDelete('cascade');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_pets');

        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn(['duration_minutes', 'base_fee', 'additional_fee', 'credits_cost']);
        });

        Schema::table('vet_profiles', function (Blueprint $table) {
            $table->dropColumn(['additional_pet_fee', 'additional_pet_duration']);
        });
    }
};
