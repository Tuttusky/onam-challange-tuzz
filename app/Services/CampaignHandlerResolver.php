<?php

namespace App\Services;

use App\Domains\Campaign\Contracts\CampaignHandlerInterface;
use App\Domains\Campaign\Handlers\StubCampaignHandler;
use Illuminate\Contracts\Container\Container;

class CampaignHandlerResolver
{
    public function __construct(
        protected Container $container,
    ) {}

    public function forType(string $type): CampaignHandlerInterface
    {
        /** @var array<string, class-string<CampaignHandlerInterface>> $handlers */
        $handlers = $this->container->make('campaign.handlers');
        $handlerClass = $handlers[$type] ?? StubCampaignHandler::class;

        return $this->container->make($handlerClass);
    }
}
