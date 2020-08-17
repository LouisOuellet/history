# PHP MySQL History class

The History class is an extension to the Database class. Little heads up though. This class will increase the amount of queries to your MySQL database. In exchange, you will have a complete log of all the transactions done into your database.

## Change Log
 * [2020-08-17] - Remodeled the CRUD Methods
 * [2020-08-16] - Uploaded to GitHub

## Requirements for the Database Class
 * PHP
 * MySQL
 * Database Class

### SQL history Table
 * id int(11) AUTO_INCREMENT PRIMARY
 * created datetime
 * modified datetime
 * action varchar(255)
 * table varchar(255)
 * before TEXT
 * after TEXT
 * ip varchar(255)

## Testing environment
### Hardware
 * Dual-Core Intel® Core™ i5-4310U CPU @ 2.00GHz
 * Intel Corporation Haswell-ULT Integrated Graphics Controller (rev 0b)
 * 7.9 GB memory
 * 471.5 GB storage (SATA SSD)
### Software
 * elementary OS 5.1.7 Hera
 * Apache/2.4.39 (Unix)
 * PHP 7.3.5 (cli) (built: May  3 2019 11:55:32) ( NTS )
 * MySQL Ver 15.1 Distrib 10.1.39-MariaDB

## Usage
### Basics
```php
require_once('database.php');
require_once('history.php');
$db = new History('host','username','password','database');
```

### Example
```php
require_once('database.php');
require_once('history.php');
$db = new History('host','username','password','database');


```
