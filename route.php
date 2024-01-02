<?php
use Ofey\Logan22\component\plugins\sphere_streams\streams;
$routes = [
    [
        "method"  => "GET",
        "pattern" => "/streams/view",
        "file"    => "streams.php",
        "call"    => function() {
            streams::show_streams_draw();
        },
    ],
    [
        "method"  => "GET",
        "pattern" => "/streams/list",
        "file"    => "streams.php",
        "call"    => function() {
            streams::show_streams_list();
        },
    ]
];
