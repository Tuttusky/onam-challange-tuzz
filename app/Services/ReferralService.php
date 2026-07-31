<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Player;
use App\Models\Referral;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReferralService
{
    public function __construct(
        protected AnalyticsService $analyticsService
    ) {}

    public function applyReferral(Player $player, string $referralCode, ?Campaign $campaign = null): ?Referral
    {
        $referrer = Player::query()
            ->where('referral_code', strtoupper($referralCode))
            ->first();

        if (! $referrer) {
            throw new InvalidArgumentException('Invalid referral code.');
        }

        if ($referrer->id === $player->id) {
            throw new InvalidArgumentException('You cannot refer yourself.');
        }

        if ($player->referred_by_player_id) {
            return Referral::query()
                ->where('referred_player_id', $player->id)
                ->first();
        }

        return DB::transaction(function () use ($player, $referrer, $campaign) {
            $player->update(['referred_by_player_id' => $referrer->id]);

            $rewardPoints = (int) (WebsiteSettingsService::getReferralRewardPoints() ?? 10);

            $referral = Referral::query()->create([
                'referrer_player_id' => $referrer->id,
                'referred_player_id' => $player->id,
                'campaign_id' => $campaign?->id,
                'reward_points' => $rewardPoints,
            ]);

            $this->analyticsService->trackEvent('referral_applied', [
                'campaign_id' => $campaign?->id,
                'player_id' => $player->id,
                'meta' => [
                    'referrer_id' => $referrer->id,
                    'referral_code' => $referrer->referral_code,
                    'reward_points' => $rewardPoints,
                ],
            ]);

            return $referral;
        });
    }

    public function getReferralStats(Player $player, ?Campaign $campaign = null): array
    {
        $query = Referral::query()->where('referrer_player_id', $player->id);

        if ($campaign) {
            $query->where('campaign_id', $campaign->id);
        }

        $referrals = $query->with('referred')->get();

        return [
            'total_referrals' => $referrals->count(),
            'total_reward_points' => $referrals->sum('reward_points'),
            'referrals' => $referrals->map(fn (Referral $r) => [
                'player_name' => $r->referred->name,
                'player_uuid' => $r->referred->uuid,
                'reward_points' => $r->reward_points,
                'created_at' => $r->created_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    public function findPlayerByReferralCode(string $code): ?Player
    {
        return Player::query()
            ->where('referral_code', strtoupper($code))
            ->first();
    }
}
