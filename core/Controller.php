<?php

namespace Core;

class Controller {

    protected $load;

    public function __construct() {
        $this->load = new Load;
    }

}

