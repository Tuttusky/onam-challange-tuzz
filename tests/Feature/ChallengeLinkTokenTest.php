<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\ChallengeLink;
use App\Models\Player;
use App\Models\PlayerSession;
use Database\Seeders\CampaignSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChallengeLinkTokenTest extends TestCase
{
    use RefreshDatabase;

    protected ChallengeLink $link;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CampaignSeeder::class);

        $campaign = Campaign::query()->where('slug', 'onam-dare-challenge')->firstOrFail();
        $creator = Player::query()->create(['name' => 'Creator']);
        $creatorSession = PlayerSession::query()->create([
            'campaign_id' => $campaign->id,
            'player_id' => $creator->id,
            'role' => 'creator',
            'status' => 'completed',
            'token_hash' => hash('sha256', 'creator-token'),
            'completed_at' => now(),
        ]);

        $this->link = ChallengeLink::query()->create([
            'campaign_id' => $campaign->id,
            'creator_session_id' => $creatorSession->id,
            'friend_name' => 'Friend',
            'challenge_title' => 'Beat me!',
            'is_active' => true,
            'is_finalized' => true,
            'expires_at' => now()->addDay(),
        ]);
    }

    public function test_challenge_can_be_fetched_by_secure_token(): void
    {
        $token = $this->link->ensureShareToken();

        $this->getJson("/api/v1/challenges/{$token}")
            ->assertOk()
            ->assertJsonPath('data.token', $token)
            ->assertJsonPath('data.challenge.token', $token);
    }

    public function test_legacy_short_code_still_resolves_challenge(): void
    {
        $this->getJson("/api/v1/challenges/{$this->link->code}")
            ->assertOk()
            ->assertJsonPath('data.code', $this->link->code)
            ->assertJsonPath('data.token', $this->link->ensureShareToken());
    }

    public function test_invalid_challenge_identifier_returns_not_found(): void
    {
        $this->getJson('/api/v1/challenges/not-a-real-token-value')
            ->assertNotFound();
    }

    public function test_share_card_uses_token_in_challenge_url(): void
    {
        $token = $this->link->ensureShareToken();

        $this->getJson("/api/v1/share-cards/{$token}")
            ->assertOk()
            ->assertJsonPath('data.challenge_url', url('/challenge/'.$token));
    }
}
