<?php

use App\Models\ChallengeLink;
use App\Services\ChallengeLinkService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('challenge_links', 'token')) {
            return;
        }

        $linkService = app(ChallengeLinkService::class);

        ChallengeLink::query()
            ->whereNull('token')
            ->orderBy('id')
            ->each(function (ChallengeLink $link) use ($linkService): void {
                $link->forceFill([
                    'token' => $linkService->generateSecureToken(),
                ])->save();
            });
    }

    public function down(): void
    {
        //
    }
};
