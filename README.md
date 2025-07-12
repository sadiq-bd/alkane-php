<p align="center">
  <img src="https://api.sadiq.workers.dev/app/github/repo/alkane-php/views" alt="Repository Views" />
</p>

<h1 align="center">Alkane PHP v2.1</h1>
<p align="center">The lightweight, flexible PHP framework for rapid and robust development.</p>

---

## 🚀 Features

- Minimal, expressive routing
- Simple, chainable database queries
- Powerful session management
- PSR-4 autoloading support
- Easy integration with Apache or Nginx
- Composer-ready

---

## 📦 Installation

```bash
composer create-project sadiq-bd/alkane-php
```

---

## 📝 Quick Start

### Routing

```php
use Core\Router;

// Basic route
Router::get('/', function() {
    return 'Hello World';
});

// Route with parameter
Router::get('/user/{id}', function($param) {
    return 'User ID - ' . $param['id'];
});

// Route with controller
Router::get('/home', App\Controller\HomeController::class, 'method');
```

---

### Database Querying

```php
$db = Core\Database::getInstance();
// or: $db = new Alkane\Database(?$custom_connection_name);

$sql = new Core\SqlQuery($db);
$sql->select(['ID', 'name', 'email'])
    ->from('table')
    ->where('ID = :id', ['id' => 20]);

$result = $sql->exec();
print_r($result->fetch(Core\SqlQuery::FETCH_ASSOC));
```

---

### Session Management

```php
Core\SessionController::set('mail.smtp.host', 'smtp.gmail.com');
Core\SessionController::set('mail.smtp.user', 'user@gmail.com');
Core\SessionController::set('mail.smtp.password', 'your_password');

// Retrieve session data
print_r(Core\SessionController::get('mail.smtp'));

/* Output:
Array (
    [host] => smtp.gmail.com
    [user] => user@gmail.com
    [password] => your_password
)
*/
```

---

## 🌐 Web Server Configuration

### Apache

Add the following rewrite rules to your `.htaccess`:

```
RewriteEngine On
RewriteRule ^(.*)$ index.php [L,QSA]

ErrorDocument 400 /index.php
ErrorDocument 401 /index.php
ErrorDocument 403 /index.php
ErrorDocument 404 /index.php
ErrorDocument 500 /index.php
ErrorDocument 502 /index.php
ErrorDocument 503 /index.php
```

### Nginx

Paste this in your server block:

```
location / {
    rewrite ^(.*)$ /index.php?$1 last;
}
```

---

## 📚 Documentation

Official documentation is coming soon. For now, see the example code above or explore the source!

---

## 🤝 Contributing

Pull requests and issues are welcome! For major changes, please open an issue first to discuss what you would like to change.

---

## 📄 License

This project is licensed under the MIT License.

---

## 💬 Contact

- [Sadiq Ahmed](https://github.com/sadiq-bd)

---
