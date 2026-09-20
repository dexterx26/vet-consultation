<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ClientProfile;
use App\Models\VetProfile;
use App\Models\VetDocument;
use App\Models\AnimalType;
use App\Models\Breed;
use App\Models\Pet;
use App\Models\VetAvailability;
use App\Models\Consultation;
use App\Models\ConsultationRecord;
use App\Models\AppNotification;
use App\Models\Review;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin User
        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin@vetconsult.com',
            'phone' => '09170000000',
            'role' => 'admin',
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);

        // 2. Animal Types & Breeds
        $dogType = AnimalType::create(['name' => 'Dog', 'icon' => 'fa-dog', 'is_active' => true]);
        $catType = AnimalType::create(['name' => 'Cat', 'icon' => 'fa-cat', 'is_active' => true]);
        $birdType = AnimalType::create(['name' => 'Bird', 'icon' => 'fa-feather', 'is_active' => true]);
        $rabbitType = AnimalType::create(['name' => 'Rabbit', 'icon' => 'fa-ghost', 'is_active' => true]);
        $hamsterType = AnimalType::create(['name' => 'Hamster', 'icon' => 'fa-otter', 'is_active' => true]);
        $reptileType = AnimalType::create(['name' => 'Reptile', 'icon' => 'fa-dragon', 'is_active' => true]);
        $otherType = AnimalType::create(['name' => 'Other', 'icon' => 'fa-paw', 'is_active' => true]);

        // Dog Breeds
        Breed::create(['animal_type_id' => $dogType->id, 'name' => 'Golden Retriever']);
        $shihTzu = Breed::create(['animal_type_id' => $dogType->id, 'name' => 'Shih Tzu']);
        Breed::create(['animal_type_id' => $dogType->id, 'name' => 'German Shepherd']);
        Breed::create(['animal_type_id' => $dogType->id, 'name' => 'Labrador Retriever']);
        Breed::create(['animal_type_id' => $dogType->id, 'name' => 'Aspin / Mixed Breed']);

        // Cat Breeds
        $persian = Breed::create(['animal_type_id' => $catType->id, 'name' => 'Persian Cat']);
        Breed::create(['animal_type_id' => $catType->id, 'name' => 'Siamese Cat']);
        Breed::create(['animal_type_id' => $catType->id, 'name' => 'Domestic Short Hair']);

        // 3. Vet Users
        // Vet 1 (Approved)
        $vet1 = User::create([
            'name' => 'Dr. Maria Santos, DVM',
            'email' => 'dr.maria@vetconsult.com',
            'phone' => '09181112233',
            'role' => 'veterinarian',
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);

        $vet1Profile = VetProfile::create([
            'user_id' => $vet1->id,
            'license_number' => 'PRC-VET-09823',
            'clinic_name' => 'St. Francis Companion Animal Clinic',
            'clinic_address' => '124 Katipunan Ave, Quezon City',
            'years_experience' => 8,
            'expertise' => 'Small Animal Internal Medicine, Surgical Care, Canine & Feline Dermatology',
            'animals_handled' => ['Dog', 'Cat', 'Rabbit'],
            'consultation_fee' => 500.00,
            'bio' => 'Passionate licensed veterinarian with 8 years of clinical experience specializing in pet health, remote diagnosis, and nutritional guidance.',
            'languages' => 'English, Tagalog',
            'city' => 'Quezon City',
            'province' => 'Metro Manila',
            'latitude' => 14.6500,
            'longitude' => 121.0500,
            'is_available' => true,
        ]);

        VetDocument::create([
            'user_id' => $vet1->id,
            'document_type' => 'professional_license',
            'document_name' => 'PRC_Vet_License_Santos.pdf',
            'file_path' => 'documents/sample_license.pdf',
            'status' => 'verified',
            'admin_notes' => 'Verified with PRC database.',
            'verified_at' => now(),
        ]);

        // Add availability for Vet 1 (Mon - Sat)
        for ($day = 1; $day <= 6; $day++) {
            VetAvailability::create([
                'user_id' => $vet1->id,
                'day_of_week' => $day,
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'is_active' => true,
            ]);
        }

        // Vet 2 (Approved)
        $vet2 = User::create([
            'name' => 'Dr. Juan Reyes, DVM',
            'email' => 'dr.juan@vetconsult.com',
            'phone' => '09192223344',
            'role' => 'veterinarian',
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);

        VetProfile::create([
            'user_id' => $vet2->id,
            'license_number' => 'PRC-VET-04112',
            'clinic_name' => 'Manila Vet Specialists',
            'clinic_address' => '450 España Blvd, Sampaloc, Manila',
            'years_experience' => 12,
            'expertise' => 'Exotic Pet Care, Avian Medicine, Preventive Teleconsultation',
            'animals_handled' => ['Dog', 'Cat', 'Bird', 'Hamster', 'Reptile'],
            'consultation_fee' => 600.00,
            'bio' => 'Senior veterinarian specializing in exotic pets, birds, and emergency triage for small animals.',
            'languages' => 'English, Tagalog',
            'city' => 'Manila',
            'province' => 'Metro Manila',
            'latitude' => 14.5995,
            'longitude' => 120.9842,
            'is_available' => true,
        ]);

        VetDocument::create([
            'user_id' => $vet2->id,
            'document_type' => 'professional_license',
            'document_name' => 'PRC_Vet_License_Reyes.pdf',
            'file_path' => 'documents/sample_license.pdf',
            'status' => 'verified',
            'admin_notes' => 'Verified.',
            'verified_at' => now(),
        ]);

        // Vet 3 (Pending Verification)
        $vet3 = User::create([
            'name' => 'Dr. Angela Cruz, DVM',
            'email' => 'dr.angela@vetconsult.com',
            'phone' => '09203334455',
            'role' => 'veterinarian',
            'status' => 'pending',
            'password' => Hash::make('password'),
        ]);

        VetProfile::create([
            'user_id' => $vet3->id,
            'license_number' => 'PRC-VET-11045',
            'clinic_name' => 'Makati Pet Wellness Center',
            'clinic_address' => 'Avala Ave, Makati City',
            'years_experience' => 4,
            'expertise' => 'Feline Medicine, Nutrition & Behavior',
            'animals_handled' => ['Cat', 'Dog'],
            'consultation_fee' => 450.00,
            'bio' => 'Dedicated cat and dog specialist focused on stress-free consultations and preventive healthcare.',
            'languages' => 'English, Tagalog',
            'city' => 'Makati',
            'province' => 'Metro Manila',
            'latitude' => 14.5547,
            'longitude' => 121.0244,
            'is_available' => false,
        ]);

        VetDocument::create([
            'user_id' => $vet3->id,
            'document_type' => 'professional_license',
            'document_name' => 'Angela_Cruz_PRC_License.pdf',
            'file_path' => 'documents/sample_license.pdf',
            'status' => 'pending',
            'admin_notes' => 'Awaiting admin review.',
        ]);

        // 4. Client User & Pets
        $client = User::create([
            'name' => 'John Doe',
            'email' => 'client@gmail.com',
            'phone' => '09178889900',
            'role' => 'client',
            'status' => 'active',
            'password' => Hash::make('password'),
        ]);

        ClientProfile::create([
            'user_id' => $client->id,
            'address' => '78 Commonwealth Avenue',
            'city' => 'Quezon City',
            'province' => 'Metro Manila',
            'country' => 'Philippines',
            'latitude' => 14.6760,
            'longitude' => 121.0437,
        ]);

        $pet1 = Pet::create([
            'user_id' => $client->id,
            'name' => 'Max',
            'animal_type_id' => $dogType->id,
            'breed_id' => $shihTzu->id,
            'sex' => 'Male',
            'dob' => '2023-04-12',
            'age_text' => '3 years old',
            'weight' => '6.5 kg',
            'color' => 'White & Brown',
            'medical_notes' => 'Slight skin irritation on left ear.',
            'existing_conditions' => 'Mild ear allergies',
            'allergies' => 'Chicken protein',
            'current_medications' => 'Antihistamine cream',
            'vaccination_info' => '5-in-1 Vaccine updated June 2026, Anti-Rabies updated July 2026.',
        ]);

        $pet2 = Pet::create([
            'user_id' => $client->id,
            'name' => 'Bella',
            'animal_type_id' => $catType->id,
            'breed_id' => $persian->id,
            'sex' => 'Female',
            'dob' => '2024-01-20',
            'age_text' => '2 years old',
            'weight' => '3.8 kg',
            'color' => 'Fluffy White',
            'medical_notes' => 'Healthy, indoor cat.',
            'existing_conditions' => 'None',
            'allergies' => 'None known',
            'current_medications' => 'Multivitamins',
            'vaccination_info' => '4-in-1 Feline Vaccine updated Jan 2026.',
        ]);

        // 5. Sample Consultation (Accepted / Upcoming)
        $consultation1 = Consultation::create([
            'consultation_number' => 'VET-202609-1001',
            'client_id' => $client->id,
            'vet_id' => $vet1->id,
            'pet_id' => $pet1->id,
            'type' => 'video',
            'status' => 'accepted',
            'scheduled_at' => Carbon::now()->addHours(2),
            'fee' => 500.00,
            'reason' => 'Max has been scratching his ears frequently for the past 2 days and showing signs of discomfort.',
        ]);

        // 6. Sample Consultation (Completed with Record & Review)
        $consultation2 = Consultation::create([
            'consultation_number' => 'VET-202609-1000',
            'client_id' => $client->id,
            'vet_id' => $vet1->id,
            'pet_id' => $pet2->id,
            'type' => 'chat',
            'status' => 'completed',
            'scheduled_at' => Carbon::now()->subDays(5),
            'fee' => 500.00,
            'reason' => 'Routine wellness check for Bella and dietary recommendations.',
        ]);

        ConsultationRecord::create([
            'consultation_id' => $consultation2->id,
            'symptoms' => 'No active disease symptoms reported. Pet is alert and active.',
            'assessment' => 'Healthy adult Persian cat with optimal body condition score (3/5).',
            'recommendations' => 'Maintain current high-protein dry kibble paired with wet cat food twice daily for hydration.',
            'treatment_advice' => 'Apply topical flea prevention monthly.',
            'medication_info' => 'Pet vitamins (1 ml daily).',
            'follow_up_instructions' => 'Follow up in 6 months for annual revaccination.',
            'additional_notes' => 'Client advised to monitor water intake during warm weather.',
        ]);

        Review::create([
            'consultation_id' => $consultation2->id,
            'client_id' => $client->id,
            'vet_id' => $vet1->id,
            'rating' => 5,
            'comment' => 'Dr. Santos was extremely patient and gave clear advice for Bella! Highly recommended.',
        ]);

        // Notifications
        AppNotification::create([
            'user_id' => $client->id,
            'title' => 'Consultation Request Accepted!',
            'message' => 'Dr. Maria Santos accepted your consultation request for Max on ' . Carbon::now()->addHours(2)->format('M d, Y g:i A') . '.',
            'type' => 'booking',
            'is_read' => false,
        ]);

        // 7. System Settings
        \App\Models\SystemSetting::set('booking_credits_cost', 300, 'Booking Credit Cost', 'Number of credits deducted when a booking is confirmed.');
        \App\Models\SystemSetting::set('video_call_time_limit_minutes', 1, 'Video Call Limit (Minutes)', 'Duration limit for video teleconsultation calls.');
        \App\Models\SystemSetting::set('default_additional_pet_fee', 250.00, 'Default Additional Pet Fee (₱)', 'Default consultation fee added for each extra pet.');
        \App\Models\SystemSetting::set('default_additional_pet_duration', 15, 'Default Additional Pet Duration (Minutes)', 'Default time added to consultation for each extra pet.');
        \App\Models\SystemSetting::set('additional_pet_credits_cost', 150, 'Additional Pet Credit Cost', 'Number of additional credits required for each extra pet.');

        // Initialize client credits
        $client->update(['credits' => 900]);
        \App\Models\CreditTransaction::create([
            'user_id' => $client->id,
            'amount' => 900,
            'type' => 'admin_topup',
            'balance_after' => 900,
            'notes' => 'Initial welcome credits for testing',
        ]);
    }
}
