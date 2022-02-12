<?php

namespace MichaelRubel\Couponables\Services\Contracts;

use Illuminate\Database\Eloquent\Model;
use MichaelRubel\Couponables\Exceptions\OverLimitException;
use MichaelRubel\Couponables\Exceptions\InvalidCouponException;
use MichaelRubel\Couponables\Exceptions\OverQuantityException;
use MichaelRubel\Couponables\Exceptions\NotAllowedToRedeemException;
use MichaelRubel\Couponables\Exceptions\CouponExpiredException;
use MichaelRubel\Couponables\Models\Contracts\CouponContract;

interface CouponServiceContract
{
    /**
     * Verify if promotional code is valid otherwise throw an exception.
     *
     * @param string $code
     * @param Model  $redeemer
     *
     * @return CouponContract
     * @throws OverQuantityException
     * @throws OverLimitException
     * @throws NotAllowedToRedeemException
     * @throws CouponExpiredException
     * @throws InvalidCouponException
     */
    public function verifyCoupon(string $code, Model $redeemer): CouponContract;

    /**
     * Apply the coupon.
     *
     * @param CouponContract $coupon
     * @param Model          $redeemer
     *
     * @return CouponContract
     */
    public function applyCoupon(CouponContract $coupon, Model $redeemer): CouponContract;
}
