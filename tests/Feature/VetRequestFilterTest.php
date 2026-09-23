<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Pet;
use App\Models\AnimalType;
use App\Models\Consultation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VetRequestFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $vet;
    private User $client;
    private AnimalType $dogType;
    private AnimalType $catType;
    private AnimalType $birdType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vet = User::factory()->create([
            'role' => 'veterinarian',
            'status' => 'active',
        ]);

        $this->client = User::factory()->create([
            'role' => 'client',
            'name' => 'Charlie Brown',
        ]);

        $this->dogType = AnimalType::create(['name' => 'Dog', 'icon' => 'fa-dog', 'is_active' => true]);
        $this->catType = AnimalType::create(['name' => 'Cat', 'icon' => 'fa-cat', 'is_active' => true]);
        $this->birdType = AnimalType::create(['name' => 'Bird', 'icon' => 'fa-feather', 'is_active' => true]);
    }

    public function test_vet_requests_page_loads_with_filter_controls(): void
    {
        $response = $this->actingAs($this->vet)->get(route('vet.requests.index'));

        $response->assertOk();
        $response->assertSee('Filter Requests');
        $response->assertSee('Consultation Date');
        $response->assertSee('Client Name');
        $response->assertSee('Pet Type');
        $response->assertSee('All Pet Types');
        $response->assertSee('Dog');
        $response->assertSee('Cat');
        $response->assertSee('Bird');
    }

    public function test_vet_can_filter_requests_by_date(): void
    {
        $pet = Pet::create([
            'user_id' => $this->client->id,
            'name' => 'Fido',
            'animal_type_id' => $this->dogType->id,
            'sex' => 'Male',
        ]);

        $date1 = Carbon::today()->addDays(2)->format('Y-m-d');
        $date2 = Carbon::today()->addDays(5)->format('Y-m-d');

        $consult1 = Consultation::create([
            'consultation_number' => 'CN-TEST-DATE1',
            'client_id' => $this->client->id,
            'vet_id' => $this->vet->id,
            'pet_id' => $pet->id,
            'type' => 'chat',
            'status' => 'pending',
            'fee' => 500.00,
            'scheduled_at' => $date1 . ' 10:00:00',
            'reason' => 'Checkup for date 1',
        ]);

        $consult2 = Consultation::create([
            'consultation_number' => 'CN-TEST-DATE2',
            'client_id' => $this->client->id,
            'vet_id' => $this->vet->id,
            'pet_id' => $pet->id,
            'type' => 'chat',
            'status' => 'pending',
            'fee' => 500.00,
            'scheduled_at' => $date2 . ' 14:00:00',
            'reason' => 'Checkup for date 2',
        ]);

        // Filter by date 1
        $response = $this->actingAs($this->vet)->get(route('vet.requests.index', ['date' => $date1]));
        $response->assertOk();
        $response->assertSee('CN-TEST-DATE1');
        $response->assertDontSee('CN-TEST-DATE2');

        // Filter by date 2
        $response2 = $this->actingAs($this->vet)->get(route('vet.requests.index', ['date' => $date2]));
        $response2->assertOk();
        $response2->assertSee('CN-TEST-DATE2');
        $response2->assertDontSee('CN-TEST-DATE1');
    }

    public function test_vet_can_filter_requests_by_client_name(): void
    {
        $clientAlice = User::factory()->create(['name' => 'Alice Wonderland', 'role' => 'client']);
        $clientBob = User::factory()->create(['name' => 'Bob Marley', 'role' => 'client']);

        $petAlice = Pet::create([
            'user_id' => $clientAlice->id,
            'name' => 'Cheshire',
            'animal_type_id' => $this->dogType->id,
            'sex' => 'Female',
        ]);

        $petBob = Pet::create([
            'user_id' => $clientBob->id,
            'name' => 'Rasta',
            'animal_type_id' => $this->dogType->id,
            'sex' => 'Male',
        ]);

        $consultAlice = Consultation::create([
            'consultation_number' => 'CN-ALICE-01',
            'client_id' => $clientAlice->id,
            'vet_id' => $this->vet->id,
            'pet_id' => $petAlice->id,
            'type' => 'video',
            'status' => 'pending',
            'fee' => 600.00,
            'scheduled_at' => Carbon::now()->addDays(1),
            'reason' => 'Alice consultation',
        ]);

        $consultBob = Consultation::create([
            'consultation_number' => 'CN-BOB-01',
            'client_id' => $clientBob->id,
            'vet_id' => $this->vet->id,
            'pet_id' => $petBob->id,
            'type' => 'video',
            'status' => 'pending',
            'fee' => 600.00,
            'scheduled_at' => Carbon::now()->addDays(1),
            'reason' => 'Bob consultation',
        ]);

        // Filter for Alice
        $response = $this->actingAs($this->vet)->get(route('vet.requests.index', ['client_name' => 'Alice']));
        $response->assertOk();
        $response->assertSee('CN-ALICE-01');
        $response->assertSee('Alice Wonderland');
        $response->assertDontSee('CN-BOB-01');

        // Filter for Marley
        $responseBob = $this->actingAs($this->vet)->get(route('vet.requests.index', ['client_name' => 'Marley']));
        $responseBob->assertOk();
        $responseBob->assertSee('CN-BOB-01');
        $responseBob->assertSee('Bob Marley');
        $responseBob->assertDontSee('CN-ALICE-01');
    }

    public function test_vet_can_filter_requests_by_pet_type(): void
    {
        $dogPet = Pet::create([
            'user_id' => $this->client->id,
            'name' => 'Doggo Special',
            'animal_type_id' => $this->dogType->id,
            'sex' => 'Male',
        ]);

        $catPet = Pet::create([
            'user_id' => $this->client->id,
            'name' => 'Kitty Special',
            'animal_type_id' => $this->catType->id,
            'sex' => 'Female',
        ]);

        $consultDog = Consultation::create([
            'consultation_number' => 'CN-PET-DOG',
            'client_id' => $this->client->id,
            'vet_id' => $this->vet->id,
            'pet_id' => $dogPet->id,
            'type' => 'chat',
            'status' => 'pending',
            'fee' => 500.00,
            'scheduled_at' => Carbon::now()->addDays(3),
            'reason' => 'Dog checkup',
        ]);

        $consultCat = Consultation::create([
            'consultation_number' => 'CN-PET-CAT',
            'client_id' => $this->client->id,
            'vet_id' => $this->vet->id,
            'pet_id' => $catPet->id,
            'type' => 'chat',
            'status' => 'pending',
            'fee' => 500.00,
            'scheduled_at' => Carbon::now()->addDays(3),
            'reason' => 'Cat checkup',
        ]);

        // Filter by Dog animal type ID
        $responseDog = $this->actingAs($this->vet)->get(route('vet.requests.index', ['pet_type' => $this->dogType->id]));
        $responseDog->assertOk();
        $responseDog->assertSee('CN-PET-DOG');
        $responseDog->assertSee('Doggo Special');
        $responseDog->assertDontSee('CN-PET-CAT');

        // Filter by Cat animal type ID
        $responseCat = $this->actingAs($this->vet)->get(route('vet.requests.index', ['pet_type' => $this->catType->id]));
        $responseCat->assertOk();
        $responseCat->assertSee('CN-PET-CAT');
        $responseCat->assertSee('Kitty Special');
        $responseCat->assertDontSee('CN-PET-DOG');
    }

    public function test_vet_can_filter_requests_by_pet_type_in_multi_pet_consultation(): void
    {
        $primaryDog = Pet::create([
            'user_id' => $this->client->id,
            'name' => 'Primary Canine',
            'animal_type_id' => $this->dogType->id,
            'sex' => 'Male',
        ]);

        $secondaryBird = Pet::create([
            'user_id' => $this->client->id,
            'name' => 'Secondary Feather',
            'animal_type_id' => $this->birdType->id,
            'sex' => 'Female',
        ]);

        $multiConsult = Consultation::create([
            'consultation_number' => 'CN-MULTI-PET',
            'client_id' => $this->client->id,
            'vet_id' => $this->vet->id,
            'pet_id' => $primaryDog->id,
            'type' => 'video',
            'status' => 'pending',
            'fee' => 750.00,
            'scheduled_at' => Carbon::now()->addDays(2),
            'reason' => 'Multi-pet checkup',
        ]);

        $multiConsult->pets()->attach([
            $primaryDog->id => ['is_primary' => true],
            $secondaryBird->id => ['is_primary' => false],
        ]);

        // Filter by Bird type: should find this consultation because the secondary pet is a Bird
        $responseBird = $this->actingAs($this->vet)->get(route('vet.requests.index', ['pet_type' => $this->birdType->id]));
        $responseBird->assertOk();
        $responseBird->assertSee('CN-MULTI-PET');

        // Filter by Dog type: should find this consultation as well
        $responseDog = $this->actingAs($this->vet)->get(route('vet.requests.index', ['pet_type' => $this->dogType->id]));
        $responseDog->assertOk();
        $responseDog->assertSee('CN-MULTI-PET');
    }

    public function test_vet_can_combine_multiple_filters(): void
    {
        $clientTarget = User::factory()->create(['name' => 'Diana Prince', 'role' => 'client']);
        $clientOther = User::factory()->create(['name' => 'Bruce Wayne', 'role' => 'client']);

        $targetPet = Pet::create([
            'user_id' => $clientTarget->id,
            'name' => 'Jumpa',
            'animal_type_id' => $this->catType->id,
            'sex' => 'Female',
        ]);

        $otherPet = Pet::create([
            'user_id' => $clientOther->id,
            'name' => 'Ace',
            'animal_type_id' => $this->dogType->id,
            'sex' => 'Male',
        ]);

        $targetDate = Carbon::today()->addDays(4)->format('Y-m-d');
        $otherDate = Carbon::today()->addDays(7)->format('Y-m-d');

        $targetConsult = Consultation::create([
            'consultation_number' => 'CN-TARGET-MATCH',
            'client_id' => $clientTarget->id,
            'vet_id' => $this->vet->id,
            'pet_id' => $targetPet->id,
            'type' => 'chat',
            'status' => 'pending',
            'fee' => 500.00,
            'scheduled_at' => $targetDate . ' 09:00:00',
            'reason' => 'Target consultation',
        ]);

        $otherConsult = Consultation::create([
            'consultation_number' => 'CN-OTHER-NO-MATCH',
            'client_id' => $clientOther->id,
            'vet_id' => $this->vet->id,
            'pet_id' => $otherPet->id,
            'type' => 'chat',
            'status' => 'pending',
            'fee' => 500.00,
            'scheduled_at' => $otherDate . ' 11:00:00',
            'reason' => 'Other consultation',
        ]);

        // Query with Date + Client Name + Pet Type
        $response = $this->actingAs($this->vet)->get(route('vet.requests.index', [
            'date' => $targetDate,
            'client_name' => 'Diana',
            'pet_type' => $this->catType->id,
        ]));

        $response->assertOk();
        $response->assertSee('CN-TARGET-MATCH');
        $response->assertDontSee('CN-OTHER-NO-MATCH');
    }
}
