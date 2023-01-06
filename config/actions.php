<?php
return [

    'listeners' => [

        // Automatically discover asListener events
        'discovery' => [
            'enabled' => false,

            /*
            / Provide own Actions Path if needed.
            / Application's `Actions` dir is used by default
            */
            'paths' => null,
//            'paths' => [
//                app_path('Actions'),
//                base_path('Modules/Example/Actions'),
//            ],


        ],

    ]
];
