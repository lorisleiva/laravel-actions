<?php

namespace Lorisleiva\Skeleton\Commands;

use Illuminate\Console\Command;

class SkeletonCommand extends Command
{
    public $signature = 'skeleton';
    public $description = 'TODO';

    public function handle()
    {
        $this->info('Done!');
    }
}
