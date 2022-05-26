<?php

namespace MichaelRubel\Couponables\Tests\Stubs\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use MichaelRubel\Couponables\Traits\HasCoupons;

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
