<?php

namespace Lorisleiva\Actions\Concerns;

use Illuminate\Queue\SerializesModels as BaseSerializesModels;

trait SerializesModels
{
    use BaseSerializesModels {
        __sleep as protected sleepFromBaseSerializesModels;
        __wakeup as protected wakeupFromBaseSerializesModels;
        __serialize as protected serializeFromBaseSerializesModels;
        __unserialize as protected unserializeFromBaseSerializesModels;
    }

    public function __sleep()
    {
        $properties = $this->sleepFromBaseSerializesModels();

        array_walk($this->attributes, function (&$value) {
            $value = $this->getSerializedPropertyValue($value);
        });

        return array_values(array_diff($properties, [
            'request', 'runningAs', 'actingAs', 'errorBag', 'validator',
            'commandInstance', 'commandSignature', 'commandDescription',
            'getAttributesFromConstructor',
        ]));
    }

    public function __wakeup()
    {
        $this->wakeupFromBaseSerializesModels();

        array_walk($this->attributes, function (&$value) {
            $value = $this->getRestoredPropertyValue($value);
        });
    }

    public function __serialize()
    {
        array_walk($this->attributes, function (&$value) {
            $value = $this->getSerializedPropertyValue($value);
        });

        return $this->serializeFromBaseSerializesModels();
    }

    public function __unserialize(array $values)
    {
        $this->unserializeFromBaseSerializesModels($values);

        array_walk($this->attributes, function (&$value) {
            $value = $this->getRestoredPropertyValue($value);
        });
    }
}
