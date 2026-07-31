<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\PottuImage;
use Database\Seeders\PottuCampaignSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PottuCustomImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected string $slug = 'sundarikk-pottu-thodal';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(PottuCampaignSeeder::class);
    }

    public function test_upload_custom_image_creates_record_and_stores_file(): void
    {
        $file = $this->fakeJpegUpload();

        $response = $this->postJson("/api/v1/campaigns/{$this->slug}/pottu/images", [
            'image' => $file,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Custom Image');

        $this->assertGreaterThan(0, $response->json('data.width'));
        $this->assertGreaterThan(0, $response->json('data.height'));

        $imageId = $response->json('data.id');
        $this->assertNotNull($imageId);

        $image = PottuImage::query()->findOrFail($imageId);
        $this->assertSame('Custom Image', $image->title);
        $this->assertFalse($image->is_active);
        $this->assertSame(999, $image->sort_order);
        $this->assertStringContainsString('pottu-custom-images/', $image->path);
        $this->assertTrue($image->is_custom);
        $this->assertNotNull($image->expires_at);

        Storage::disk('public')->assertExists($image->path);
    }

    public function test_uploaded_custom_image_is_excluded_from_public_config(): void
    {
        $activeCount = PottuImage::query()
            ->whereHas('campaign', fn ($query) => $query->where('slug', $this->slug))
            ->where('is_active', true)
            ->count();

        $file = $this->fakeJpegUpload();

        $upload = $this->postJson("/api/v1/campaigns/{$this->slug}/pottu/images", [
            'image' => $file,
        ]);

        $upload->assertCreated();
        $customImageId = $upload->json('data.id');

        $config = $this->getJson("/api/v1/campaigns/{$this->slug}/pottu/config");

        $config->assertOk()
            ->assertJsonPath('success', true);

        $imageIds = collect($config->json('data.images'))->pluck('id')->all();

        $this->assertCount($activeCount, $imageIds);
        $this->assertNotContains($customImageId, $imageIds);
    }

    public function test_upload_rejects_non_image_file(): void
    {
        $file = UploadedFile::fake()->create('notes.txt', 10, 'text/plain');

        $response = $this->postJson("/api/v1/campaigns/{$this->slug}/pottu/images", [
            'image' => $file,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);

        $this->assertSame(4, PottuImage::query()->where('is_active', true)->count());
    }

    public function test_upload_rejects_missing_file(): void
    {
        $response = $this->postJson("/api/v1/campaigns/{$this->slug}/pottu/images", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);
    }

    public function test_upload_returns_404_for_unknown_campaign(): void
    {
        $file = $this->fakeJpegUpload();

        $response = $this->postJson('/api/v1/campaigns/unknown-campaign/pottu/images', [
            'image' => $file,
        ]);

        $response->assertNotFound();
    }

    public function test_upload_returns_422_for_inactive_campaign(): void
    {
        Campaign::query()
            ->where('slug', $this->slug)
            ->update(['status' => 'draft']);

        $file = $this->fakeJpegUpload();

        $response = $this->postJson("/api/v1/campaigns/{$this->slug}/pottu/images", [
            'image' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Campaign is not active.');
    }
}
