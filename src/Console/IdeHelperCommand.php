<?php

namespace Lorisleiva\Actions\Console;

use Illuminate\Console\Command;
use Lorisleiva\Actions\IdeHelper\ActionInfoFactory;
use Lorisleiva\Actions\IdeHelper\BuildIdeHelper;

class IdeHelperCommand extends Command
{
    public $signature = 'ide-helper:actions';

    public $description = 'Generate a new IDE Helper file for Laravel Actions.';

    public function handle()
    {
        $actionsPath = app_path('Actions');
        $outfile = base_path('_ide_helper_actions.php');

        $actionInfos = ActionInfoFactory::create($actionsPath);
        $result = BuildIdeHelper::create()->build($actionInfos);

        file_put_contents($outfile, $result);

        $this->comment('Action information was written to ' . basename($outfile));
    }
}
