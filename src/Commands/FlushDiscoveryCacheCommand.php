<?php

namespace Lorisleiva\Actions\Commands;

use Illuminate\Console\Command;
use Lorisleiva\Actions\ActionManager;

class FlushDiscoveryCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'actions:flush-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes discovered actions from cache.';
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
        $flushed = $this->manager->flushDiscoveryCache();
        $this->info($flushed ? 'Action discovery cache was flushed' : 'No action discovery cache found');
    }
}
