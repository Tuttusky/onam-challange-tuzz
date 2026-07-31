<?php

namespace Tests\Feature;

use App\Domains\Campaign\Handlers\PottuChallengeHandler;
use App\Models\Campaign;
use App\Models\ChallengeLink;
use App\Models\Player;
use App\Models\PlayerSession;
use App\Models\PottuImage;
use App\Models\PottuPlacement;
use Database\Seeders\PottuCampaignSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PottuCustomImageChallengeTest extends TestCase
{
    use RefreshDatabase;

    protected string $slug = 'sundarikk-pottu-thodal';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(PottuCampaignSeeder::class);
    }

    public function test_challenger_bootstrap_includes_uploaded_custom_image(): void
    {
        $campaign = Campaign::query()->where('slug', $this->slug)->firstOrFail();

        $customImage = $this->uploadCustomImage();

        $creator = Player::query()->create(['name' => 'Creator']);
        $creatorSession = PlayerSession::query()->create([
            'campaign_id' => $campaign->id,
            'player_id' => $creator->id,
            'role' => 'creator',
            'status' => 'completed',
            'token_hash' => hash('sha256', 'creator-token'),
            'completed_at' => now(),
        ]);

        $link = ChallengeLink::query()->create([
            'campaign_id' => $campaign->id,
            'creator_session_id' => $creatorSession->id,
            'friend_name' => 'Friend',
            'challenge_title' => 'Find my pottu!',
            'is_active' => true,
            'is_finalized' => true,
            'expires_at' => now()->addDay(),
        ]);

        PottuPlacement::query()->create([
            'player_session_id' => $creatorSession->id,
            'pottu_image_id' => $customImage->id,
            'x' => 0.45,
            'y' => 0.35,
            'size' => 52,
            'rotation' => 0,
            'board_width' => 400,
            'board_height' => 600,
            'attempt_count' => 1,
        ]);

        $challenger = Player::query()->create(['name' => 'Friend']);
        $challengerSession = PlayerSession::query()->create([
            'campaign_id' => $campaign->id,
            'player_id' => $challenger->id,
            'role' => 'challenger',
            'status' => 'started',
            'challenge_link_id' => $link->id,
            'parent_session_id' => $creatorSession->id,
            'token_hash' => hash('sha256', 'challenger-token'),
        ]);

        $handler = app(PottuChallengeHandler::class);
        $payload = $handler->bootstrapPlay($campaign, $challengerSession->fresh(['challengeLink.creatorSession.player']));

        $this->assertSame('pottu', $payload['mode']);
        $this->assertSame($customImage->id, $payload['selected_image_id']);

        $imageIds = collect($payload['images'])->pluck('id')->all();
        $this->assertContains($customImage->id, $imageIds);
    }

    protected function uploadCustomImage(): PottuImage
    {
        $file = $this->fakeJpegUpload();

        $response = $this->postJson("/api/v1/campaigns/{$this->slug}/pottu/images", [
            'image' => $file,
        ]);

        $response->assertCreated();

        return PottuImage::query()->findOrFail($response->json('data.id'));
    }
}
