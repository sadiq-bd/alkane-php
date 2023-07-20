<?php
declare(strict_types = 1);

// Configuration file
require_once __DIR__ . '/app/config.php';

// include all functions
foreach (glob(__DIR__ . '/core/functions/*.php') as $file) {
    require_once $file;
}


// autoload classes
require_once __DIR__ . '/vendor/autoload.php';

// set Database configs
foreach ($dbconf as $key => $val) {
    Core\Database::setConfig($key, $val);
} 

// proccess routes and run the app
require_once __DIR__ . '/app/routes.php';


