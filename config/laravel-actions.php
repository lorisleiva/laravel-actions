<?php

return [
    /*
     * In order to register your actions as Artisan commands,
     * the package Service Provider needs to know where to find the classes.
     */
    'discovery' => [
        // Absolute paths to the folders to scan (recursively)
        'folders' => [
            app_path('Actions')
        ],
        'classes' => [
            // Manually specify classes to discover (fully qualified names)
        ],
        /*
         * Caching discovered actions is highly recommended for production environments,
         * but may be inconvenient during development
         */
        'caching' => [
            'enabled' => env('ACTIONS_CACHE', true),
            // Automatically flushes the cache after composer dumps autoload files
            'auto-flush' => env('ACTIONS_AUTO_FLUSH_CACHE', true),
        ]
    ],
];
