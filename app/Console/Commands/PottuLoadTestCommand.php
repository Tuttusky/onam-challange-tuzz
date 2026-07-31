<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class PottuLoadTestCommand extends Command
{
    protected $signature = 'loadtest:pottu
        {--players=10000 : Number of virtual players}
        {--concurrency=30 : Parallel workers}
        {--base=http://127.0.0.1:8002 : API base URL}';

    protected $description = 'Run Scenario 01 load test (creator start → placement → finalize)';

    public function handle(): int
    {
        $players = (int) $this->option('players');
        $concurrency = max(1, (int) $this->option('concurrency'));
        $base = rtrim((string) $this->option('base'), '/');
        $script = base_path('load-tests/scenario-01-creator-flow.mjs');

        if (! file_exists($script)) {
            $this->error('Load test script not found: '.$script);

            return self::FAILURE;
        }

        $health = Http::timeout(5)->get($base.'/up');
        if (! $health->successful()) {
            $this->error("Server not reachable at {$base}. Start with LOAD_TEST_MODE=true on port 8002.");

            return self::FAILURE;
        }

        $this->info("Scenario 01 — {$players} players, concurrency {$concurrency}");
        $this->line("Target: {$base}");
        $this->newLine();

        $process = new \Symfony\Component\Process\Process([
            'node',
            $script,
            "--players={$players}",
            "--concurrency={$concurrency}",
            "--base={$base}",
        ], base_path());

        $process->setTimeout(null);
        $process->run(function ($type, $buffer): void {
            $this->output->write($buffer);
        });

        return $process->isSuccessful() ? self::SUCCESS : self::FAILURE;
    }
}
