<?php

namespace Tests\Feature;

use App\Models\AnimalType;
use App\Models\Pet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetBirthdayAndAgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_client_can_register_pet_with_birthday_and_age_is_dynamically_calculated()
    {
        $client = User::where('role', 'client')->first();
        $dogType = AnimalType::where('name', 'Dog')->first();

        // Pet born 2 years and 3 months ago from today
        $dob = Carbon::now()->subYears(2)->subMonths(3)->format('Y-m-d');

        $response = $this->actingAs($client)->post(route('client.pets.store'), [
            'name' => 'Rocky',
            'animal_type_id' => $dogType->id,
            'sex' => 'Male',
            'dob' => $dob,
            'weight' => '12 kg',
            'color' => 'Black & Tan',
        ]);

        $response->assertRedirect(route('client.pets.index'));
        $response->assertSessionHas('success');

        $pet = Pet::where('name', 'Rocky')->first();
        $this->assertNotNull($pet);
        $this->assertEquals($dob, $pet->dob->format('Y-m-d'));
        $this->assertEquals('2 years 3 months old', $pet->age_text);
    }

    public function test_pet_birthday_cannot_be_in_the_future()
    {
        $client = User::where('role', 'client')->first();
        $dogType = AnimalType::where('name', 'Dog')->first();

        $futureDob = Carbon::now()->addDays(5)->format('Y-m-d');

        $response = $this->actingAs($client)->post(route('client.pets.store'), [
            'name' => 'FuturePup',
            'animal_type_id' => $dogType->id,
            'sex' => 'Female',
            'dob' => $futureDob,
        ]);

        $response->assertSessionHasErrors('dob');
        $this->assertDatabaseMissing('pets', ['name' => 'FuturePup']);
    }

    public function test_updating_pet_birthday_recalculates_dynamic_age()
    {
        $client = User::where('role', 'client')->first();
        $pet = $client->pets()->first();
        $this->assertNotNull($pet);

        // Update to 8 months ago
        $newDob = Carbon::now()->subMonths(8)->format('Y-m-d');

        $response = $this->actingAs($client)->put(route('client.pets.update', $pet), [
            'name' => $pet->name,
            'animal_type_id' => $pet->animal_type_id,
            'sex' => $pet->sex,
            'dob' => $newDob,
        ]);

        $response->assertRedirect(route('client.pets.index'));

        $pet->refresh();
        $this->assertEquals($newDob, $pet->dob->format('Y-m-d'));
        $this->assertEquals('8 months old', $pet->age_text);
    }

    public function test_legacy_pets_without_dob_fallback_to_stored_age_text()
    {
        $client = User::where('role', 'client')->first();
        $dogType = AnimalType::where('name', 'Dog')->first();

        $legacyPet = Pet::create([
            'user_id' => $client->id,
            'name' => 'Oldie',
            'animal_type_id' => $dogType->id,
            'sex' => 'Male',
            'dob' => null,
            'age_text' => '5 years old',
        ]);

        $this->assertEquals('5 years old', $legacyPet->age_text);
    }

    public function test_registration_form_shows_birthday_field_and_calculator()
    {
        $client = User::where('role', 'client')->first();

        $response = $this->actingAs($client)->get(route('client.pets.create'));
        $response->assertOk();
        $response->assertSee('Birthday *');
        $response->assertSee('name="dob"', false);
        $response->assertSee('petBirthdayCalculator', false);
        $response->assertDontSee('name="age_text"', false);
    }
}
