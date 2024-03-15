![Laravel Couponables](https://user-images.githubusercontent.com/37669560/224356302-5c021249-2290-4fb4-8bbe-99fbef4b5df5.png)

# Laravel Couponables
[![Latest Version on Packagist](https://img.shields.io/packagist/v/michael-rubel/laravel-couponables.svg?style=flat-square&logo=packagist)](https://packagist.org/packages/michael-rubel/laravel-couponables)
[![Tests](https://img.shields.io/github/actions/workflow/status/michael-rubel/laravel-couponables/run-tests.yml?branch=main&style=flat-square&label=tests&logo=github)](https://github.com/michael-rubel/laravel-couponables/actions)
[![Code Quality](https://img.shields.io/scrutinizer/quality/g/michael-rubel/laravel-couponables.svg?style=flat-square&logo=scrutinizer)](https://scrutinizer-ci.com/g/michael-rubel/laravel-couponables/?branch=main)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/michael-rubel/laravel-couponables.svg?style=flat-square&logo=scrutinizer)](https://scrutinizer-ci.com/g/michael-rubel/laravel-couponables/?branch=main)
[![Infection](https://img.shields.io/github/actions/workflow/status/michael-rubel/laravel-couponables/infection.yml?branch=main&style=flat-square&label=infection&logo=php)](https://github.com/michael-rubel/laravel-couponables/actions)
[![Larastan](https://img.shields.io/github/actions/workflow/status/michael-rubel/laravel-couponables/phpstan.yml?branch=main&style=flat-square&label=larastan&logo=laravel)](https://github.com/michael-rubel/laravel-couponables/actions)

This package provides coupons/promocodes functionality for your Laravel application leveraging Eloquent's polymorphic relationships.

The package requires `PHP 8.1` or higher and `Laravel 10` or higher.

---

## #StandWithUkraine
[![SWUbanner](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner2-direct.svg)](https://github.com/vshymanskyy/StandWithUkraine/blob/main/docs/README.md)

---

## Installation
Install the package using composer:
```bash
composer require michael-rubel/laravel-couponables
```

Publish the migrations:
```bash
php artisan vendor:publish --tag="couponables-migrations"
```

Publish the config file:
```bash
php artisan vendor:publish --tag="couponables-config"
```

---

## Usage
After publishing migrations, apply a trait in the model you want to use as a `$redeemer`:
```php
use HasCoupons;
```

---

### Artisan command
You can add coupons to your database using Artisan command:
```bash
php artisan make:coupon YourCouponCode
```

Optionally, you can pass the next arguments:
```php
'--value'         // The 'value' to perform calculations based on the coupon provided
'--type'          // The 'type' to point out the calculation strategy
'--limit'         // Limit how many times the coupon can be applied by the model
'--quantity'      // Limit how many coupons are available overall (this value will decrement)
'--expires_at'    // Set expiration time for the coupon
'--redeemer_type' // Polymorphic model type. Can as well be morph-mapped value, i.e. 'users'
'--redeemer_id'   // Redeemer model ID
'--data'          // JSON column to store any metadata you want for this particular coupon
```

#### Adding coupons using model
You can as well add coupons simply using model:
```php
Coupon::create([
    'code'  => '...',
    'type'  => '...'
    'value' => '...',
    ...
]);
```

- **Note:** `type` and `value` columns are used for cost calculations (this is optional).\
If the `type` column is `null`, the `subtraction` strategy will be chosen.

---

### Basic operations
Verify the coupon code:
```php
$redeemer->verifyCoupon($code);
```

Redeem the coupon:
```php
$redeemer->redeemCoupon($code);
```

Redeem the coupon in context of another model:
```php
$redeemer
  ->redeemCoupon($code)
  ->for($course);
```

Combined `redeemCoupon` and `for` behavior (assuming the `$course` includes `HasCoupons` trait):
```php
$course->redeemBy($redeemer, $code);
```

If something's going wrong, methods `verifyCoupon` and `redeemCoupon` will throw an exception:

```php
CouponDisabledException     // Coupon is disabled (`is_enabled` column).
CouponExpiredException      // Coupon is expired (`expires_at` column).
InvalidCouponException      // Coupon is not found in the database.
InvalidCouponTypeException  // Wrong coupon type found in the database (`type` column).
InvalidCouponValueException // Wrong coupon value passed from the database (`value` column).
NotAllowedToRedeemException // Coupon is assigned to the specific model (`redeemer` morphs).
OverLimitException          // Coupon is over the limit for the specific model (`limit` column).
OverQuantityException       // Coupon is exhausted (`quantity` column).
CouponException             // Generic exception for all cases.
```

If you want to bypass the exception and do something else:
```php
$redeemer->verifyCouponOr($code, function ($code, $exception) {
    // Your action with $code or $exception!
});
```

```php
$redeemer->redeemCouponOr($code, function ($code, $exception) {
    // Your action with $code or $exception!
});
```

### [Redeemer](https://github.com/michael-rubel/laravel-couponables/blob/main/src/HasCoupons.php) checks
Check if this coupon is already used by the model:
```php
$redeemer->isCouponAlreadyUsed($code);
```

Check if the coupon is over the limit for the model:
```php
$redeemer->isCouponOverLimit($code);
```

### [Coupon](https://github.com/michael-rubel/laravel-couponables/blob/main/src/Models/Coupon.php) checks
```php
public function isExpired(): bool;
public function isNotExpired(): bool;
public function isDisposable(): bool;
public function isOverQuantity(): bool;
public function isRedeemedBy(Model $redeemer): bool;
public function isOverLimitFor(Model $redeemer): bool;
```

This method references the model assigned to redeem the coupon:
```php
public function redeemer(): ?Model;
```

### Calculations

```php
$coupon = Coupon::create([
    'code'  => 'my-generated-coupon-code-to-use',
    'type'  => CouponContract::TYPE_PERCENTAGE, // 'percentage'
    'value' => '10', // <-- %10
]);

$coupon->calc(using: 300); // 270.00
```

The package supports three types of item cost calculations:
- `subtraction` - subtracts the given value from the value defined in the coupon model;
- `percentage` - subtracts the given value by the percentage defined in the coupon model;
- `fixed` - completely ignores the given value and takes the coupon model value instead.

Note: you can find constants for coupon types in the [`CouponContract`](https://github.com/michael-rubel/laravel-couponables/blob/main/src/Models/Contracts/CouponContract.php)

### Listeners
If you go event-driven, you can handle package events:
- [CouponVerified](https://github.com/michael-rubel/laravel-couponables/blob/main/src/Events/CouponVerified.php)
- [CouponRedeemed](https://github.com/michael-rubel/laravel-couponables/blob/main/src/Events/CouponRedeemed.php)
- [CouponExpired](https://github.com/michael-rubel/laravel-couponables/blob/main/src/Events/CouponExpired.php)
- [CouponIsOverLimit](https://github.com/michael-rubel/laravel-couponables/blob/main/src/Events/CouponIsOverLimit.php)
- [CouponIsOverQuantity](https://github.com/michael-rubel/laravel-couponables/blob/main/src/Events/CouponIsOverQuantity.php)
- [NotAllowedToRedeem](https://github.com/michael-rubel/laravel-couponables/blob/main/src/Events/NotAllowedToRedeem.php)

---

### Extending package functionality
Traits [DefinesColumns](https://github.com/michael-rubel/laravel-couponables/blob/main/src/Models/Traits/DefinesColumns.php) and [DefinesPivotColumns](https://github.com/michael-rubel/laravel-couponables/blob/main/src/Models/Traits/DefinesPivotColumns.php) contain the methods that define column names to use by the package, so you can use inheritance to override them.

If you need to override the entire classes, use the [config values](https://github.com/michael-rubel/laravel-couponables/blob/main/config/couponables.php) or [container bindings](https://github.com/michael-rubel/laravel-couponables/blob/main/src/CouponableServiceProvider.php). All the classes in the package have their own contract (interface), so you're free to modify it as you wish.

`CouponService` has the `Macroable` trait, This way you can inject the methods to interact with the service without overriding anything.


## Contributing
If you see any ways we can improve the package, PRs are welcome. But remember to write tests for your use cases.

## Testing
```bash
composer test
```
