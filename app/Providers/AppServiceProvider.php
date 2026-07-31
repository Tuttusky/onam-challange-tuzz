<?php

namespace App\Providers;

use App\Models\Campaign;
use App\Models\Player;
use App\Models\Question;
use App\Policies\CampaignPolicy;
use App\Policies\PlayerPolicy;
use App\Policies\QuestionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Campaign::class, CampaignPolicy::class);
        Gate::policy(Question::class, QuestionPolicy::class);
        Gate::policy(Player::class, PlayerPolicy::class);
    }
}
