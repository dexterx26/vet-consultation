<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained()->onDelete('cascade');
            $table->text('symptoms')->nullable();
            $table->text('assessment')->nullable();
            $table->text('recommendations')->nullable();
            $table->text('treatment_advice')->nullable();
            $table->text('medication_info')->nullable();
            $table->text('follow_up_instructions')->nullable();
            $table->text('additional_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_records');
    }
};
