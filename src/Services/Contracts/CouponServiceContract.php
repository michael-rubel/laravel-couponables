<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Services\Contracts;

use Illuminate\Database\Eloquent\Model;
use MichaelRubel\Couponables\Models\Contracts\CouponContract;

interface CouponServiceContract
{
    public function getCoupon(?string $code): ?CouponContract;
    public function verifyCoupon(?string $code, ?Model $redeemer = null): CouponContract;
    public function performBasicChecks(CouponContract $coupon, ?Model $redeemer = null): CouponContract;
    public function applyCoupon(CouponContract $coupon, Model $redeemer, ?Model $redeemed): CouponContract;
}
