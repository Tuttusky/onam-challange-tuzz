<?php

namespace App\Console\Commands;

use App\Services\Pottu\PottuCustomImageService;
use Illuminate\Console\Command;

class PurgeExpiredPottuCustomImagesCommand extends Command
{
    protected $signature = 'pottu:purge-custom-images';

    protected $description = 'Delete expired custom pottu photos from storage and the database';

    public function handle(PottuCustomImageService $customImageService): int
    {
        $purged = $customImageService->purgeExpired();

        $this->components->info("Purged {$purged} expired custom photo(s).");

        return self::SUCCESS;
    }
}
