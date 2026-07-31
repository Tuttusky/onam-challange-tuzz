<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\ChallengeLink;
use App\Models\PottuImage;
use App\Services\Pottu\PottuCustomImageService;
use Database\Seeders\PottuCampaignSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PottuCustomImageRetentionTest extends TestCase
{
    use RefreshDatabase;

    protected string $slug = 'sundarikk-pottu-thodal';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(PottuCampaignSeeder::class);
    }

    public function test_upload_sets_custom_flag_and_expiry(): void
    {
        $response = $this->postJson("/api/v1/campaigns/{$this->slug}/pottu/images", [
            'image' => $this->fakeJpegUpload(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.is_custom', true)
            ->assertJsonPath('data.privacy_notice', 'Custom photos are stored for 7 days only and then deleted from our servers.');

        $image = PottuImage::query()->findOrFail($response->json('data.id'));

        $this->assertTrue($image->is_custom);
        $this->assertNotNull($image->expires_at);
        $this->assertTrue($image->expires_at->greaterThan(now()->addDays(6)));
        $this->assertTrue($image->expires_at->lessThanOrEqualTo(now()->addDays(7)->addMinute()));
    }

    public function test_purge_removes_expired_custom_images_and_files(): void
    {
        $upload = $this->postJson("/api/v1/campaigns/{$this->slug}/pottu/images", [
            'image' => $this->fakeJpegUpload(),
        ])->assertCreated();

        $image = PottuImage::query()->findOrFail($upload->json('data.id'));
        $path = $image->path;

        Storage::disk('public')->assertExists($path);

        $image->update(['expires_at' => now()->subMinute()]);

        $purged = app(PottuCustomImageService::class)->purgeExpired();

        $this->assertSame(1, $purged);
        $this->assertDatabaseMissing('pottu_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_expired_custom_image_is_not_served_in_public_array(): void
    {
        $image = PottuImage::query()->create([
            'campaign_id' => Campaign::query()->where('slug', $this->slug)->value('id'),
            'title' => 'Custom Image',
            'path' => 'pottu-custom-images/expired.jpg',
            'width' => 100,
            'height' => 100,
            'sort_order' => 999,
            'is_active' => false,
            'is_custom' => true,
            'expires_at' => now()->subDay(),
        ]);

        $this->assertSame('', $image->fresh()->url);
        $this->assertTrue($image->fresh()->isExpired());
    }

    public function test_pottu_config_exposes_privacy_settings(): void
    {
        $response = $this->getJson("/api/v1/campaigns/{$this->slug}/pottu/config");

        $response->assertOk()
            ->assertJsonPath('data.settings.custom_image_retention_days', 7)
            ->assertJsonPath('data.settings.custom_challenge_valid_days', 7);
    }
}
