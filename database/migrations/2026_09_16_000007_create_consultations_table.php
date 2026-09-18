<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->string('consultation_number')->unique();
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('vet_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('pet_id')->constrained('pets')->onDelete('cascade');
            $table->enum('type', ['chat', 'video'])->default('video');
            $table->enum('status', [
                'pending',
                'accepted',
                'reschedule_suggested',
                'scheduled',
                'in_progress',
                'completed',
                'declined',
                'cancelled_by_client',
                'cancelled_by_vet',
                'expired'
            ])->default('pending');
            $table->dateTime('scheduled_at');
            $table->decimal('fee', 8, 2);
            $table->text('reason');
            $table->json('attachments')->nullable();
            $table->text('decline_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
