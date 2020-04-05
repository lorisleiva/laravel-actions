<?php

return [
    /*
     * In order to register your actions as Artisan commands,
     * the package Service Provider needs to know where to find the classes.
     * Please select which strategies to use for this discovery.
     */
    'discovery' => [
        /*
         * Use Composer's autoloader class-map to automatically discover actions.
         * Note: new actions will only be discoverable after Composer has updated the autoload files (dump-autoload).
         */
        'autoloader' => env('ACTIONS_AUTOLOADER_DISCOVERY', true),
        /*
         * Scan the specified folders recursively.
         */
        'folders' => [
            // Absolute paths to the folders to scan
        ],
        'classes' => [
            // Manually specify classes to discover (fully qualified names)
        ],
        /*
         * Caching discovered actions is highly recommended for production environments,
         * but may be inconvenient during development
         */
        'caching' => [
            'enabled' => true,
            'ttl' => -1, // Cache lifetime in seconds, or -1 to remember
            'cacheKey' => 'laravel-actions:discovered'
        ]
    ],
];
