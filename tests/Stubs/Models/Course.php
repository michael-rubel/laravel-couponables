<?php

namespace MichaelRubel\Couponables\Tests\Stubs\Models;

use Illuminate\Database\Eloquent\Model;
use MichaelRubel\Couponables\HasCoupons;

class Course extends Model
{
    use HasCoupons;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = [];
}
