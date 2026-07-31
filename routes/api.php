<?php

use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\CampaignController;
use App\Http\Controllers\Api\V1\ChallengeController;
use App\Http\Controllers\Api\V1\CmsController;
use App\Http\Controllers\Api\V1\FriendMediaController;
use App\Http\Controllers\Api\V1\LeaderboardController;
use App\Http\Controllers\Api\V1\PlayerController;
use App\Http\Controllers\Api\V1\PottuController;
use App\Http\Controllers\Api\V1\SessionController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\ShareCardController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware(array_values(array_filter([
        filter_var(env('LOAD_TEST_MODE', false), FILTER_VALIDATE_BOOL) ? null : 'throttle:api',
        'maintenance',
    ])))
    ->group(function (): void {
        Route::get('campaigns/active', [CampaignController::class, 'active']);
        Route::get('campaigns/{slug}', [CampaignController::class, 'show']);
        Route::get('campaigns/{slug}/pottu/config', [PottuController::class, 'config']);
        Route::post('campaigns/{slug}/pottu/images', [PottuController::class, 'uploadImage']);

        Route::post('players', [PlayerController::class, 'store']);

        Route::post('friend-media', [FriendMediaController::class, 'store'])
            ->middleware('throttle:10,1');
        Route::get('friend-media/avatars', [FriendMediaController::class, 'avatars']);
        Route::get('friend-media/{token}', [FriendMediaController::class, 'show'])
            ->name('api.friend-media.show');

        Route::post('sessions/start', [SessionController::class, 'start'])
            ->middleware(filter_var(env('LOAD_TEST_MODE', false), FILTER_VALIDATE_BOOL) ? [] : 'throttle:20,1');

        Route::middleware('player.session')->group(function (): void {
            Route::get('sessions/{uuid}/questions', [SessionController::class, 'questions']);
            Route::post('sessions/{uuid}/answers', [SessionController::class, 'submitAnswers']);
            Route::post('sessions/{uuid}/pottu-placement', [SessionController::class, 'submitPottuPlacement']);
            Route::post('sessions/{uuid}/finalize', [SessionController::class, 'finalize']);

            Route::post('challenges/{token}/sessions/{uuid}/answers', [ChallengeController::class, 'submitAnswers']);
            Route::post('challenges/{token}/sessions/{uuid}/pottu-placement', [ChallengeController::class, 'submitPottuPlacement']);
        });

        Route::get('challenges/{token}', [ChallengeController::class, 'show']);
        Route::post('challenges/{token}/join', [ChallengeController::class, 'join']);
        Route::post('challenges/{token}/shares', [ChallengeController::class, 'recordShare']);
        Route::post('challenges/{token}/rematch', [ChallengeController::class, 'rematch']);
        Route::get('challenges/{token}/results', [ChallengeController::class, 'results']);

        Route::get('share-cards/{token}', [ShareCardController::class, 'show']);
        Route::get('share-cards/{token}/image', [ShareCardController::class, 'image'])
            ->name('api.share-cards.image');

        Route::get('leaderboard/{period?}', [LeaderboardController::class, 'index'])
            ->whereIn('period', ['daily', 'weekly', 'monthly', 'overall']);

        Route::get('cms/{slug}', [CmsController::class, 'show']);

        Route::get('settings/public', [SettingsController::class, 'public']);

        Route::post('analytics/visit', [AnalyticsController::class, 'trackVisit']);
    });
