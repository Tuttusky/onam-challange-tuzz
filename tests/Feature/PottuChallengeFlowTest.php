<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\PottuImage;
use Database\Seeders\PottuCampaignSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PottuChallengeFlowTest extends TestCase
{
    use RefreshDatabase;

    protected string $slug = 'sundarikk-pottu-thodal';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PottuCampaignSeeder::class);
    }

    public function test_creator_to_challenger_flow_completes_with_results(): void
    {
        $creatorName = 'FlowCreator';
        $challengerName = 'FlowFriend';
        $malayalamTitle = 'FlowCreator നിങ്ങളെ വെല്ലുവിളിച്ചു!';
        $malayalamMessage = 'FlowCreator നിങ്ങളെ വെല്ലുവിളിച്ചു! കൃത്യമായ പൊട്ടു സ്ഥാനം കണ്ടെത്താൻ കഴിയുമോ?';

        $start = $this->postJson('/api/v1/sessions/start', [
            'campaign_slug' => $this->slug,
            'name' => $creatorName,
            'friend_name' => 'Friend',
            'challenge_title' => $malayalamTitle,
            'challenge_message' => $malayalamMessage,
        ]);

        $start->assertCreated()
            ->assertJsonPath('data.session.role', 'creator')
            ->assertJsonPath('data.questions.mode', 'pottu');

        $creatorSessionUuid = $start->json('data.session.uuid');
        $creatorToken = $start->json('data.token');
        $imageId = $start->json('data.questions.images.0.id');

        $this->assertNotEmpty($creatorSessionUuid);
        $this->assertNotEmpty($creatorToken);
        $this->assertNotEmpty($imageId);

        $creatorPlacement = [
            'image_id' => $imageId,
            'x' => 0.48,
            'y' => 0.32,
            'size' => 52,
            'rotation' => 0,
            'board_width' => 400,
            'board_height' => 600,
        ];

        $this->withHeader('X-Player-Session', $creatorToken)
            ->postJson("/api/v1/sessions/{$creatorSessionUuid}/pottu-placement", $creatorPlacement)
            ->assertOk()
            ->assertJsonPath('data.saved', true);

        $finalize = $this->withHeader('X-Player-Session', $creatorToken)
            ->postJson("/api/v1/sessions/{$creatorSessionUuid}/finalize");

        $finalize->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $challengeToken = $finalize->json('data.challenge_token');
        $this->assertNotEmpty($challengeToken);

        $this->getJson("/api/v1/challenges/{$challengeToken}")
            ->assertOk()
            ->assertJsonPath('data.challenge.creator.name', $creatorName)
            ->assertJsonPath('data.challenge.challenge_title', $malayalamTitle)
            ->assertJsonPath('data.challenge.challenge_message', $malayalamMessage)
            ->assertJsonPath('data.challenge.is_finalized', true);

        $join = $this->postJson("/api/v1/challenges/{$challengeToken}/join", [
            'name' => $challengerName,
        ]);

        $join->assertCreated()
            ->assertJsonPath('data.session.role', 'challenger')
            ->assertJsonPath('data.questions.mode', 'pottu')
            ->assertJsonPath('data.questions.selected_image_id', $imageId);

        $challengerSessionUuid = $join->json('data.session.uuid');
        $challengerToken = $join->json('data.token');

        $challengerPlacement = [
            'image_id' => $imageId,
            'x' => 0.50,
            'y' => 0.34,
            'size' => 52,
            'rotation' => 0,
            'board_width' => 400,
            'board_height' => 600,
        ];

        $this->withHeader('X-Player-Session', $challengerToken)
            ->postJson("/api/v1/challenges/{$challengeToken}/sessions/{$challengerSessionUuid}/pottu-placement", $challengerPlacement)
            ->assertOk()
            ->assertJsonPath('data.saved', true);

        $this->withHeader('X-Player-Session', $challengerToken)
            ->postJson("/api/v1/sessions/{$challengerSessionUuid}/finalize")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $results = $this->getJson("/api/v1/challenges/{$challengeToken}/results?challenger_uuid={$challengerSessionUuid}");

        $results->assertOk()
            ->assertJsonPath('data.creator.name', $creatorName)
            ->assertJsonPath('data.challenger.name', $challengerName)
            ->assertJsonPath('data.accuracy', fn ($value) => $value !== null)
            ->assertJsonPath('data.all_friends.0.name', $challengerName)
            ->assertJsonPath('data.top_winner.name', $challengerName)
            ->assertJsonPath('data.creator_position.x', 0.48)
            ->assertJsonPath('data.friend_position.x', 0.50);
    }

    public function test_challenge_cannot_be_joined_before_creator_finalizes(): void
    {
        $start = $this->postJson('/api/v1/sessions/start', [
            'campaign_slug' => $this->slug,
            'name' => 'EarlyCreator',
        ])->assertCreated();

        $token = $start->json('data.challenge_link.token')
            ?? $start->json('data.questions.challenge_token');

        $this->assertNotEmpty($token);

        $this->postJson("/api/v1/challenges/{$token}/join", [
            'name' => 'TooEarly',
        ])->assertStatus(422);
    }

    public function test_pottu_campaign_has_active_images_for_image_step(): void
    {
        $campaign = Campaign::query()->where('slug', $this->slug)->firstOrFail();

        $count = PottuImage::query()
            ->where('campaign_id', $campaign->id)
            ->where('is_active', true)
            ->count();

        $this->assertGreaterThan(0, $count);

        $this->getJson("/api/v1/campaigns/{$this->slug}/pottu/config")
            ->assertOk()
            ->assertJsonPath('data.images.0.id', fn ($id) => $id !== null);
    }
}
