<?php

return [
    "cache_duration" => 60,
    "cache_file_name" => "uploads/cache/streams.json",

    // streamers list and configurations
    "streams" => [
        // if enabled, offline streams will be listed
        "show_offline" => false,

        "twitch" => [
            "access_token" => "YOUR_ACCESS_TOKEN",
            "client_id" => "YOUR_CLIENT_ID",

            "streamers" => [
                [
                    "username" => "pashavvp",
                    "sphere_user_id" => 0
                ],
                [
                    "username" => "izshrek",
                    "sphere_user_id" => 1
                ],
                [
                    "username" => "kitprofi",
                    "sphere_user_id" => 2
                ],
                [
                    "username" => "axterial",
                    "sphere_user_id" => 3
                ],
                [
                    "username" => "bohpts",
                    "sphere_user_id" => 4
                ],
                [
                    "username" => "fisher",
                    "sphere_user_id" => 5
                ],
                [
                    "username" => "dmitry_lixxx",
                    "sphere_user_id" => 6
                ]
            ]
        ],
        "youtube" => [
            "api_key" => "YOUR_API_KEY",

            "streamers" => [
                [
                    "username" => "LofiGirl",
                    "channel_id" => "UCSJ4gkVC6NrvII8umztf0Ow",
                    "sphere_user_id" => 6
                ],
                [
                    "username" => "Просто сантехник",
                    "channel_id" => "UCqzfJVH803kOXlYl_y6igAg",
                    "sphere_user_id" => 7
                ]
            ],
        ],
        "trovo" => [
            "client_id" => "YOUR_CLIENT_ID",

            "streamers" => [
                [
                    "username" => "Waldemarchique",
                    "sphere_user_id" => 8
                ],
            ],
        ],
    ],

    // stream rewards system
    "rewards" => [
        // storage for rewards info
        "rewards_filename" => "uploads/rewards.json",
        "rewards_reset_interval" => 86400, // 24h

        "records" => [
            [
                // unique id for reward record
                "id" => 0,
                // server id for reward apply
                "server_id" => 1,
                // get reward threshold(both conditions should match), if passed, user will get reward into sphere inventory
                "threshold" => [
                    // min time to get reward(in mins)
                    "duration_min" => 60,
                    // min viewvers to get reward
                    "viewers" => 5,
                ],
                "items" => [
                    [
                        "id" => 57,
                        "count" => 1000,
                        "enchant" => 0,
                    ],
                    [
                        "id" => 4357,
                        "count" => 1,
                        "enchant" => 0,
                    ],
                ]
            ],
            [
                "id" => 1,
                "server_id" => 1,
                "threshold" => [
                    "duration_min" => 120,
                    "viewers" => 20,
                ],
                "items" => [
                    [
                        "id" => 4356,
                        "count" => 100,
                        "enchant" => 0,
                    ],
                ]
            ],
        ],
    ],
];