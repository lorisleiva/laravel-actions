<?php

namespace Lorisleiva\Actions\Commands;

use Illuminate\Console\Command;
use Lorisleiva\Actions\ActionManager;

class BenchmarkActionDiscoveryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'actions:benchmark-discovery {--runs=100 : Number of iterations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Benchmark performance of action discovery';
    /**
     * @var ActionManager
     */
    private $manager;

    /**
     * Create a new command instance.
     *
     * @param ActionManager $manager
     */
    public function __construct(ActionManager $manager)
    {
        parent::__construct();
        $this->manager = $manager;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $cache_enabled = config()->get('laravel-actions.discovery.caching.enabled');
        $cold_results = collect();
        $warm_results = collect();
        $discovered = null;
        $runs = (int)$this->option('runs');
        $progress = $this->getOutput()->createProgressBar($runs);
        $this->info(sprintf('Benchmarking action discovery (%d runs)', $runs));
        $progress->start();
        for ($i = 0; $i < $runs; $i++) {
            if ($cache_enabled) {
                $this->manager->flushDiscoveryCache();
            }
            $cold_start = microtime(true);
            $actions = $this->manager->getActions();
            $cold_results->push(microtime(true) - $cold_start);
            if ($discovered === null) {
                $discovered = $actions;
            }
            if ($cache_enabled) {
                $warm_start = microtime(true);
                $this->manager->getActions();
                $warm_results->push(microtime(true) - $warm_start);
            }
            $progress->advance();
        }
        $progress->clear();
        $headers = ['', 'Average', 'Fastest', 'Slowest'];
        $rows = [
            [
                $cache_enabled ? 'Cold cache' : 'Discovery',
                $this->formatTime($cold_results->avg()),
                $this->formatTime($cold_results->min()),
                $this->formatTime($cold_results->max())
            ]
        ];
        if ($cache_enabled) {
            $rows[] = [
                'Warm cache',
                $this->formatTime($warm_results->avg()),
                $this->formatTime($warm_results->min()),
                $this->formatTime($warm_results->max())
            ];
        }
        $this->table($headers, $rows, 'box');
        $this->comment($discovered->count() . ' action(s) found');
        if ($cache_enabled) {
            $this->comment('Cached size: ' . $this->bytesToHuman(strlen(serialize($discovered))));
        }
    }

    private function formatTime(float $seconds)
    {
        return sprintf('%.2f ms', $seconds * 1000);
    }

    private function bytesToHuman($bytes)
    {
        $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
