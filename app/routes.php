<?php
use Core\Router;

$router = new Router;

$router->get('/', App\Controllers\HomeController::class);
$router->get('/home', App\Controllers\HomeController::class);


// test 
$router->get('/test', function() {
    return 'Hello World';
});


// error handles
$router->get('/error/404', App\Controllers\ErrorPageController::class);
$router->get('/error/noscript', App\Controllers\ErrorPageController::class, 'noscript');



// $router->get('/user/{int:id}', function($params) {
//     return 'User view page ; id =' . $params['id'];
// });


$router->default(App\Controllers\ErrorPageController::class, 'main');

$router->run();


