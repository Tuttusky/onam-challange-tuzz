<?php

namespace App\Providers;

use App\Domains\Campaign\Contracts\CampaignHandlerInterface;
use App\Domains\Campaign\Handlers\DareChallengeHandler;
use App\Domains\Campaign\Handlers\PottuChallengeHandler;
use App\Domains\Campaign\Handlers\StubCampaignHandler;
use App\Models\Campaign;
use Illuminate\Support\ServiceProvider;

class CampaignServiceProvider extends ServiceProvider
{
    /**
     * @var array<string, class-string<CampaignHandlerInterface>>
     */
    protected array $handlers = [
        Campaign::TYPE_DARE_CHALLENGE => DareChallengeHandler::class,
        Campaign::TYPE_POTTU => PottuChallengeHandler::class,
        Campaign::TYPE_QUIZ => StubCampaignHandler::class,
        Campaign::TYPE_POLL => StubCampaignHandler::class,
        Campaign::TYPE_SURVEY => StubCampaignHandler::class,
    ];

    public function register(): void
    {
        $this->app->singleton(DareChallengeHandler::class);
        $this->app->singleton(PottuChallengeHandler::class);
        $this->app->singleton(StubCampaignHandler::class);

        $this->app->bind(CampaignHandlerInterface::class, DareChallengeHandler::class);

        $this->app->singleton('campaign.handlers', fn () => $this->handlers);
    }

    public function boot(): void
    {
        //
    }

    public function handlerForType(string $type): CampaignHandlerInterface
    {
        $class = $this->handlers[$type] ?? StubCampaignHandler::class;

        return $this->app->make($class);
    }
}
