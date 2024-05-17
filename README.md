[repo]:  https://github.com/yordanny90/SQLManager
[iconGit]: http://www.google.com/s2/favicons?domain=www.github.com

### Esta versión aún está en desarrollo. ###

# SQLManager

Creacion y ejecución de sentencias SQL de forma segura

Su uso correcto previene el SQL Injection

[Ir a ![GitHub CI][iconGit]][repo]

# Requisitos mínimos

PHP 7.1+, PHP 8.0+

# Clases necesarias

Solo es necesario incluir la clase [SQVar](src/SQVar.php) y la carpeta de clases según del driver a utilizar:
- Carpeta de [SQLite](src/SQLiteMan)
  - Clases: [SQLiteMan](src/SQLiteMan.php) (recomendado) y [SQLiteManPDO](src/SQLiteManPDO.php)
- Carpeta de [MySQL](src/MySQLMan)
  - Clase Principal: [MySQLMan](src/MySQLMan.php)

# Compatibilidad del driver/servidor

La comprobación de la compatibilidad según la versión del driver o el servidor queda a criterio de la persona que use la librería

```php
$dsn='sqlite::memory:';
$conn=new PDO($dsn);
$conn->getAttribute(PDO::ATTR_CLIENT_VERSION);
$man=new SQLiteMan($conn);
```