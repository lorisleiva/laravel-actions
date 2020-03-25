<?php


namespace Lorisleiva\Actions\Concerns;


trait RunsAsCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    public function runAsCommand()
    {
        $this->runningAs = 'command';
        return $this->run();
    }
}
