<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Client
            $table->string('name');
            $table->foreignId('animal_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('breed_id')->nullable()->constrained()->onDelete('set null');
            $table->string('custom_breed')->nullable();
            $table->string('sex'); // Male, Female
            $table->date('dob')->nullable();
            $table->string('age_text')->nullable();
            $table->string('weight')->nullable(); // e.g. 5.2 kg
            $table->string('color')->nullable();
            $table->string('photo')->nullable();
            $table->text('medical_notes')->nullable();
            $table->text('existing_conditions')->nullable();
            $table->text('allergies')->nullable();
            $table->text('current_medications')->nullable();
            $table->text('vaccination_info')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pets');
    }
};
