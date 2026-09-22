<?php

namespace Tests\Feature;

use App\Events\ConsultationMessageSent;
use App\Models\AnimalType;
use App\Models\Breed;
use App\Models\Consultation;
use App\Models\ConsultationMessage;
use App\Models\Pet;
use App\Models\User;
use App\Models\VetProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConsultationChatUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $vet;
    private User $client;
    private Consultation $consultation;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->vet = User::factory()->create(['role' => 'veterinarian']);
        VetProfile::create([
            'user_id' => $this->vet->id,
            'license_number' => 'VET-98765',
            'years_of_experience' => 7,
            'consultation_fee' => 60.00,
            'is_verified' => true,
        ]);

        $this->client = User::factory()->create(['role' => 'client', 'credits' => 100]);

        $animalType = AnimalType::create(['name' => 'Dog', 'slug' => 'dog']);
        $breed = Breed::create(['animal_type_id' => $animalType->id, 'name' => 'Bulldog']);

        $pet = Pet::create([
            'user_id' => $this->client->id,
            'animal_type_id' => $animalType->id,
            'breed_id' => $breed->id,
            'name' => 'Max',
            'sex' => 'Male',
        ]);

        $this->consultation = Consultation::create([
            'consultation_number' => 'CN-' . strtoupper(uniqid()),
            'client_id' => $this->client->id,
            'vet_id' => $this->vet->id,
            'pet_id' => $pet->id,
            'status' => 'in_progress',
            'type' => 'chat',
            'fee' => 60.00,
            'reason' => 'Checkup',
            'scheduled_at' => now(),
            'total_duration_seconds' => 900,
            'duration_minutes' => 15,
            'time_consumed_seconds' => 0,
        ]);
    }

    public function test_client_can_upload_image_in_chat(): void
    {
        $file = UploadedFile::fake()->image('rash.jpg', 640, 480);

        $response = $this->actingAs($this->client)->postJson(route('consultation.send-message', $this->consultation), [
            'message' => 'Here is a photo of the rash',
            'attachment' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'attachment_type' => 'image',
            ],
        ]);

        $msg = ConsultationMessage::latest()->first();
        $this->assertNotNull($msg->attachment_path);
        $this->assertEquals('image', $msg->attachment_type);
        Storage::disk('public')->assertExists($msg->attachment_path);
    }

    public function test_client_can_upload_document_in_chat(): void
    {
        $file = UploadedFile::fake()->create('medical_history.pdf', 300, 'application/pdf');

        $response = $this->actingAs($this->client)->postJson(route('consultation.send-message', $this->consultation), [
            'attachment' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'attachment_type' => 'document',
            ],
        ]);

        $msg = ConsultationMessage::latest()->first();
        $this->assertNotNull($msg->attachment_path);
        $this->assertEquals('document', $msg->attachment_type);
        Storage::disk('public')->assertExists($msg->attachment_path);
    }

    public function test_client_can_upload_video_in_chat(): void
    {
        $file = UploadedFile::fake()->create('dog-walking.mp4', 5000, 'video/mp4');

        $response = $this->actingAs($this->client)->postJson(route('consultation.send-message', $this->consultation), [
            'message' => 'Watch how he walks',
            'attachment' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'attachment_type' => 'video',
            ],
        ]);

        $msg = ConsultationMessage::latest()->first();
        $this->assertNotNull($msg->attachment_path);
        $this->assertEquals('video', $msg->attachment_type);
        Storage::disk('public')->assertExists($msg->attachment_path);
    }

    public function test_client_cannot_upload_disallowed_file_type(): void
    {
        $file = UploadedFile::fake()->create('exploit.exe', 100);

        $response = $this->actingAs($this->client)->postJson(route('consultation.send-message', $this->consultation), [
            'attachment' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['attachment']);
    }

    public function test_client_can_upload_file_up_to_50mb(): void
    {
        $file = UploadedFile::fake()->create('large.mp4', 48000, 'video/mp4');

        $response = $this->actingAs($this->client)->postJson(route('consultation.send-message', $this->consultation), [
            'attachment' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'attachment_type' => 'video',
            ],
        ]);
    }

    public function test_client_cannot_upload_file_exceeding_50mb(): void
    {
        $file = UploadedFile::fake()->create('toolarge.mp4', 55000, 'video/mp4');

        $response = $this->actingAs($this->client)->postJson(route('consultation.send-message', $this->consultation), [
            'attachment' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['attachment']);
    }

    public function test_uploading_image_dispatches_reverb_broadcast_with_attachment_url(): void
    {
        Event::fake([ConsultationMessageSent::class]);

        $file = UploadedFile::fake()->image('cat.png');

        $response = $this->actingAs($this->client)->postJson(route('consultation.send-message', $this->consultation), [
            'message' => 'Cat photo',
            'attachment' => $file,
        ]);

        $response->assertStatus(200);

        Event::assertDispatched(ConsultationMessageSent::class, function ($event) {
            $data = $event->broadcastWith();
            return !empty($data['attachment_url'])
                && $data['attachment_type'] === 'image'
                && $data['message'] === 'Cat photo';
        });
    }

    public function test_uploading_video_dispatches_reverb_broadcast_with_video_type(): void
    {
        Event::fake([ConsultationMessageSent::class]);

        $file = UploadedFile::fake()->create('symptom.mp4', 4000, 'video/mp4');

        $response = $this->actingAs($this->client)->postJson(route('consultation.send-message', $this->consultation), [
            'message' => 'Video of symptoms',
            'attachment' => $file,
        ]);

        $response->assertStatus(200);

        Event::assertDispatched(ConsultationMessageSent::class, function ($event) {
            $data = $event->broadcastWith();
            return !empty($data['attachment_url'])
                && $data['attachment_type'] === 'video'
                && $data['message'] === 'Video of symptoms';
        });
    }

    public function test_vet_can_also_upload_video_in_chat(): void
    {
        $file = UploadedFile::fake()->create('instruction.mp4', 8000, 'video/mp4');

        $response = $this->actingAs($this->vet)->postJson(route('consultation.send-message', $this->consultation), [
            'message' => 'Please perform this massage technique',
            'attachment' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'attachment_type' => 'video',
                'is_me' => true,
            ],
        ]);
    }

    public function test_webm_and_mov_formats_are_recognized_as_videos(): void
    {
        $webmFile = UploadedFile::fake()->create('pet-cough.webm', 3000, 'video/webm');

        $responseWebm = $this->actingAs($this->client)->postJson(route('consultation.send-message', $this->consultation), [
            'attachment' => $webmFile,
        ]);

        $responseWebm->assertStatus(200);
        $responseWebm->assertJson([
            'status' => 'success',
            'data' => [
                'attachment_type' => 'video',
            ],
        ]);

        $movFile = UploadedFile::fake()->create('pet-eye.mov', 3000, 'video/quicktime');

        $responseMov = $this->actingAs($this->client)->postJson(route('consultation.send-message', $this->consultation), [
            'attachment' => $movFile,
        ]);

        $responseMov->assertStatus(200);
        $responseMov->assertJson([
            'status' => 'success',
            'data' => [
                'attachment_type' => 'video',
            ],
        ]);
    }

    public function test_media_compression_service_compresses_image(): void
    {
        $tempImg = tempnam(sys_get_temp_dir(), 'test_img_') . '.jpg';
        $im = imagecreatetruecolor(2000, 1500);
        $bg = imagecolorallocate($im, 80, 120, 160);
        imagefill($im, 0, 0, $bg);
        imagejpeg($im, $tempImg, 100);
        imagedestroy($im);

        $initialSize = filesize($tempImg);
        $result = \App\Services\MediaCompressionService::compress($tempImg, 'image');

        $this->assertTrue($result['success']);
        $this->assertFileExists($result['path']);
        $finalSize = filesize($result['path']);
        $this->assertLessThan($initialSize, $finalSize);

        @unlink($tempImg);
    }

    public function test_media_compression_service_locates_ffmpeg(): void
    {
        $ffmpegPath = \App\Services\MediaCompressionService::getFfmpegPath();
        $this->assertNotNull($ffmpegPath);
        $this->assertTrue(file_exists($ffmpegPath) || $ffmpegPath === 'ffmpeg');
    }
}

