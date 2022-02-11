![Laravel Couponables]()

# Laravel Couponables
[![Latest Version on Packagist](https://img.shields.io/packagist/v/michael-rubel/laravel-couponables.svg?style=flat-square&logo=packagist)](https://packagist.org/packages/michael-rubel/laravel-couponables)
[![Total Downloads](https://img.shields.io/packagist/dt/michael-rubel/laravel-couponables.svg?style=flat-square&logo=packagist)](https://packagist.org/packages/michael-rubel/laravel-couponables)
[![Code Quality](https://img.shields.io/scrutinizer/quality/g/michael-rubel/laravel-couponables.svg?style=flat-square&logo=scrutinizer)](https://scrutinizer-ci.com/g/michael-rubel/laravel-couponables/?branch=main)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/michael-rubel/laravel-couponables.svg?style=flat-square&logo=scrutinizer)](https://scrutinizer-ci.com/g/michael-rubel/laravel-couponables/?branch=main)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/michael-rubel/laravel-couponables/run-tests/main?style=flat-square&label=tests&logo=github)](https://github.com/michael-rubel/laravel-couponables/actions)
[![PHPStan](https://img.shields.io/github/workflow/status/michael-rubel/laravel-couponables/phpstan/main?style=flat-square&label=larastan&logo=laravel)](https://github.com/michael-rubel/laravel-couponables/actions)

This package provides polymorphic coupon functionality for your Laravel application.

The package requires PHP `^8.x` and Laravel `^8.71`.
Laravel 9 is supported as well.

[![PHP Version](https://img.shields.io/badge/php-^8.x-777BB4?style=flat-square&logo=php)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/laravel-^8.71-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![Laravel Octane Compatible](https://img.shields.io/badge/octane-compatible-success?style=flat-square&logo=laravel)](https://github.com/laravel/octane)

## Installation
Install the package using composer:
```bash
composer require michael-rubel/laravel-couponables
```

Publish the migration:
```bash
php artisan vendor:publish --tag="couponables-migrations"
```

Publish the config file:
```bash
php artisan vendor:publish --tag="couponables-config"
```

## Usage

## Testing
```bash
composer test
```
