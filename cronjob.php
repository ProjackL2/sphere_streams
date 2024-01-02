<?php

$root_dir = dirname(dirname(dirname(dirname(__DIR__))));
chdir($root_dir);

require $root_dir . '/vendor/autoload.php';
Ofey\Logan22\component\plugins\sphere_streams\streams::request_cache_update();
Ofey\Logan22\component\plugins\sphere_streams\rewards::check_rewards();
