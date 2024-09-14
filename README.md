[repo]:  https://github.com/yordanny90/SQLiteMan
[iconGit]: http://www.google.com/s2/favicons?domain=www.github.com

### Esta versión aún está en desarrollo. ###

# SQLiteMan

Creacion y ejecución de sentencias SQL de forma segura

Su uso correcto previene el SQL Injection

[Ir a ![GitHub CI][iconGit]][repo]

# Requisitos mínimos

PHP 7.1+, PHP 8.0+

# Compatibilidad del driver/servidor

La comprobación de la compatibilidad según la versión del driver o el servidor:

```php
$dsn='sqlite::memory:';
$conn=new PDO($dsn);
$conn->getAttribute(PDO::ATTR_CLIENT_VERSION);
$man=new SQLiteMan($conn);
```