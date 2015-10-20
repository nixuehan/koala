# What is Flight?

Flight is a fast, simple, extensible framework for PHP. Flight enables you to 
quickly and easily build RESTful web applications.

```php
require 'flight/Flight.php';

Flight::route('/', function(){
    echo 'hello world!';
});

Flight::start();
```
