# Alkane PHP v2.0
    The PHP Framework for easy and flexible Development (v2.0)

# Basic Usages

    # Router
    Core\Router::get('/', function() {
      return 'Hello World';
    });
    Core\Router::get('/user/{id}', function($param) {
      return 'User ID - ' . $param['id'];
    });
    Core\Router::get('/home', HomeControler::class, 'method');


    # Database
    $dbInstance = Core\Database::getInstance(); // or > new Alkane\Database(?$custom_connection_name);
    $sql = new Core\SqlQuery($dbInstance);
    $sql->select([      // or > $sql->select() // for * all
            'ID',
            'name',
            'email'
        ])
        ->from('table')
        ->where('ID = :id', [
            'id' => 20
        ]);
    $result = $sql->exec();
    print_r($result->fetch(Core\SqlQuery::FETCH_ASSOC));

    # Session
    Core\SessionControler::set('mail.smtp.host', 'smtp.gmail.com');
    Core\SessionControler::set('mail.smtp.user', 'user@gmail.com');
    Core\SessionControler::set('mail.smtp.password', '6456g654d26gv624');

    // get data back
    print_r(Core\SessionControler::get('mail.smtp'));
    /* ^^^^^^^^^^^ result ^^^^^^^^^^^^
        Array (
            [host] => smtp.gmail.com
            [user] => user@gmail.com
            [password] => 6456g654d26gv624
        )
    *********************************/


# Apache rewrite rule for Router
    RewriteEngine On
    RewriteRule ^(.*)$ index.php [L,QSA]
    ErrorDocument 400 /index.php
    ErrorDocument 401 /index.php
    ErrorDocument 403 /index.php
    ErrorDocument 404 /index.php
    ErrorDocument 500 /index.php
    ErrorDocument 502 /index.php
    ErrorDocument 503 /index.php


# Nginx rewrite rule for Router
    location / {
        rewrite ^(.*)$ /index.php?$1 last;   
    }
