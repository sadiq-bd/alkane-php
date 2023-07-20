<?php

namespace Core;

class Load {

    public function __construct() {
        
    }

    public function view(string $file, array $data = null) {
        if (null !== $data) {
            extract($data);
        }
        
        if (file_exists($file = __DIR__.'/../App/Views/' . $file . '.php')) {
            include $file;
        }
    }

    public function model(string $model) {
        if (file_exists($file = __DIR__.'/../App/Models/' . $model . '.php')) {
            require_once $file;
        }
        return new ('\\Model\\' . $model);
    }

}
