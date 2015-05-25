[![Total Downloads](https://img.shields.io/packagist/dt/Arrilot/laravel-api-generator.svg?style=flat)](https://packagist.org/packages/Arrilot/laravel-api-generator)
[![Scrutinizer Quality Score](https://img.shields.io/scrutinizer/g/Arrilot/laravel-api-generator/master.svg?style=flat)](https://scrutinizer-ci.com/g/Arrilot/laravel-api-generator/)
[![MIT License](https://img.shields.io/packagist/l/Arrilot/laravel-api-generator.svg?style=flat)](https://packagist.org/packages/Arrilot/laravel-api-generator)

#Laravel Api Generator

*Laravel api console generator utilizing Fractal package. Includes basic API skeleton too.*

## Installation

1 Run ```composer require arrilot/laravel-api-generator```

2) Register a service provider in the `app.php` configuration file

```php
<?php

'providers' => [
    ...
    'Arrilot\Api\ServiceProvider',
],
?>
```

## Usage