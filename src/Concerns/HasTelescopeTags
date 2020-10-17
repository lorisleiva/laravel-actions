<?php

namespace Lorisleiva\Actions\Concerns;

/**
 * Trait HasTelescopeTags
 * @package Lorisleiva\Actions\Concerns
 * @mixin \Lorisleiva\Actions\Action
 */
trait HasTelescopeTags
{
    /**
     * Get a list of Telescope tags for every Eloquent model included in the Action attributes
     * Tags for loaded relationships are included recursively
     * @param array|null $items
     * @return array
     */
    public function tags(array $items = null): array
    {
        if ($items === null) {
            $items = $this->all();
        }
        return collect($items)
            ->map(function($item) {
                if ($item instanceof \Illuminate\Database\Eloquent\Model) {
                    $model_tag = sprintf('%s:%s', \get_class($item), $item->getKey());
                    $relationship_tags = $this->tags($item->getRelations());
                    return array_merge(
                        [$model_tag],
                        [$relationship_tags]
                    );
                }
                if (\is_iterable($item)) {
                    if ($item instanceof \Illuminate\Support\Enumerable) {
                        $item = $item->all();
                    }
                    return $this->tags($item);
                }
                return null;
            })
            ->flatten() // Convert nested tags to single dimensional array
            ->filter()  // Remove null values
            ->unique()  // Models might occur more than once
            ->values()  // Force to non-associative array
            ->toArray();
    }
}
