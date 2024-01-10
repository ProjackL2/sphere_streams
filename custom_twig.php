<?php

namespace Ofey\Logan22\component\plugins\sphere_streams;

use Ofey\Logan22\component\plugins\sphere_streams\streams;

class custom_twig {

    public function get_streams() {
        return streams::load_streams();
    }

    public function get_domain() {
        return $_SERVER['HTTP_HOST'];
    }
}