<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AnalyticsSettingController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\BadgeController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\CampaignController;
use App\Http\Controllers\Admin\ChallengeLinkController;
use App\Http\Controllers\Admin\ChallengeResultController;
use App\Http\Controllers\Admin\CmsPageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LeaderboardController;
use App\Http\Controllers\Admin\LoginLogController;
use App\Http\Controllers\Admin\PlayerController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\QuestionOptionController;
use App\Http\Controllers\Admin\ReferralController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ResultMessageController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SeoSettingController;
use App\Http\Controllers\Admin\WebsiteSettingController;
use Illuminate\Support\Facades\Route;

Route::name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('login', [LoginController::class, 'login']);
    });

    Route::middleware(['auth:admin', 'log.admin.activity'])->group(function () {
        Route::post('logout', [LoginController::class, 'logout'])->name('logout');

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('campaigns', CampaignController::class)->except(['show']);
        Route::post('campaigns/{campaign}/clone', [CampaignController::class, 'clone'])->name('campaigns.clone');
        Route::patch('campaigns/{campaign}/toggle-status', [CampaignController::class, 'toggleStatus'])->name('campaigns.toggle-status');

        Route::prefix('campaigns/{campaign}')->name('campaigns.')->group(function () {
            Route::resource('questions', QuestionController::class)->except(['show']);
            Route::post('questions/reorder', [QuestionController::class, 'reorder'])->name('questions.reorder');
            Route::get('pottu-settings', [\App\Http\Controllers\Admin\CampaignPottuSettingController::class, 'edit'])->name('pottu-settings.edit');
            Route::put('pottu-settings', [\App\Http\Controllers\Admin\CampaignPottuSettingController::class, 'update'])->name('pottu-settings.update');
            Route::resource('pottu-images', \App\Http\Controllers\Admin\PottuImageController::class)->except(['show']);
            Route::resource('pottu-styles', \App\Http\Controllers\Admin\PottuStyleController::class)->except(['show']);
        });

        Route::get('pottu-photos', function () {
            $campaign = \App\Models\Campaign::query()->where('type', \App\Models\Campaign::TYPE_POTTU)->first()
                ?? \App\Models\Campaign::query()->where('slug', 'sundarikk-pottu-thodal')->first()
                ?? \App\Models\Campaign::query()->first();

            if (! $campaign) {
                return redirect()->route('admin.campaigns.index');
            }

            return redirect()->route('admin.campaigns.pottu-images.index', $campaign);
        })->name('pottu-photos');

        Route::post('questions/{question}/options', [QuestionOptionController::class, 'store'])->name('questions.options.store');
        Route::put('questions/{question}/options/{option}', [QuestionOptionController::class, 'update'])->name('questions.options.update');
        Route::delete('questions/{question}/options/{option}', [QuestionOptionController::class, 'destroy'])->name('questions.options.destroy');

        Route::get('players/export', [PlayerController::class, 'export'])->name('players.export');
        Route::resource('players', PlayerController::class)->only(['index', 'show']);

        Route::resource('challenge-links', ChallengeLinkController::class)->only(['index', 'show']);
        Route::resource('challenge-results', ChallengeResultController::class)->only(['index', 'show']);

        Route::resource('badges', BadgeController::class)->except(['show']);
        Route::resource('result-messages', ResultMessageController::class)->except(['show']);

        Route::get('leaderboards', [LeaderboardController::class, 'index'])->name('leaderboards.index');
        Route::post('leaderboards/rebuild', [LeaderboardController::class, 'rebuild'])->name('leaderboards.rebuild');

        Route::get('referrals', [ReferralController::class, 'index'])->name('referrals.index');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

        Route::resource('banners', BannerController::class)->except(['show']);
        Route::resource('cms', CmsPageController::class)->except(['show'])->parameters(['cms' => 'cmsPage']);

        Route::get('settings/website', [WebsiteSettingController::class, 'edit'])->name('settings.website');
        Route::put('settings/website', [WebsiteSettingController::class, 'update']);
        Route::get('settings/friend-challenge', [\App\Http\Controllers\Admin\FriendChallengeSettingController::class, 'edit'])->name('settings.friend-challenge');
        Route::put('settings/friend-challenge', [\App\Http\Controllers\Admin\FriendChallengeSettingController::class, 'update']);
        Route::get('settings/pottu', [\App\Http\Controllers\Admin\PottuSettingController::class, 'edit'])->name('settings.pottu');
        Route::put('settings/pottu', [\App\Http\Controllers\Admin\PottuSettingController::class, 'update']);
        Route::resource('friend-avatars', \App\Http\Controllers\Admin\FriendAvatarController::class)->except(['show']);
        Route::get('settings/seo', [SeoSettingController::class, 'edit'])->name('settings.seo');
        Route::put('settings/seo', [SeoSettingController::class, 'update']);
        Route::get('settings/analytics', [AnalyticsSettingController::class, 'edit'])->name('settings.analytics');
        Route::put('settings/analytics', [AnalyticsSettingController::class, 'update']);

        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');

        Route::get('logs/activity', [ActivityLogController::class, 'index'])->name('logs.activity');
        Route::get('logs/login', [LoginLogController::class, 'index'])->name('logs.login');

        Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
        Route::post('backups', [BackupController::class, 'create'])->name('backups.create');
    });
});
