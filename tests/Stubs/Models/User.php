<?php

namespace MichaelRubel\Couponables\Tests\Stubs\Models;

use MichaelRubel\Couponables\HasCoupons;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasCoupons;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = [];
}
