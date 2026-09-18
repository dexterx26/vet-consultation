<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vet_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('license_number')->unique();
            $table->string('clinic_name')->nullable();
            $table->string('clinic_address')->nullable();
            $table->integer('years_experience')->default(1);
            $table->text('expertise')->nullable();
            $table->json('animals_handled')->nullable();
            $table->decimal('consultation_fee', 8, 2)->default(500.00);
            $table->text('bio')->nullable();
            $table->string('languages')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('country')->default('Philippines');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vet_profiles');
    }
};
