<?php

if (!function_exists('action_route')) {
    function action_route(string|array $actionClass, $parameters = [], $absolute = true)
    {
        $actionString = is_array($actionClass) ? implode('@', $actionClass) : $actionClass;

        return route(
            'laravel-actions.route',
            array_merge(
                $parameters,
                ['actionString' => \Illuminate\Support\Facades\Crypt::encryptString($actionString)]
            ),
            $absolute
        );
    }
}
