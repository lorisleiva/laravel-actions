<?php

namespace Lorisleiva\Actions;

use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Concerns\AsObject;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\AsListener;
use Lorisleiva\Actions\Concerns\AsJob;

class Action
{
    use AsObject;
    use AsController;
    use AsListener;
    use AsJob;
    use AsCommand;
}
