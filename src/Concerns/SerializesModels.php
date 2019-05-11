<?php

namespace Lorisleiva\Actions\Concerns;

use Illuminate\Queue\SerializesModels as BaseSerializesModels;

trait SerializesModels
{
    use BaseSerializesModels { 
        __sleep as protected sleepFromBaseSerializesModels; 
    }
    
    public function __sleep()
    {
        $properties = $this->sleepFromBaseSerializesModels();

        return array_values(array_diff($properties, [
            'middleware', 'runningAs', 'errorBag', 'validator',
        ]));
    }
}