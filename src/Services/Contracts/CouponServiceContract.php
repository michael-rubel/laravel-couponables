<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Services\Contracts;

use Illuminate\Database\Eloquent\Model;
use MichaelRubel\Couponables\Exceptions\CouponDisabledException;
use MichaelRubel\Couponables\Exceptions\CouponException;
use MichaelRubel\Couponables\Exceptions\CouponExpiredException;
use MichaelRubel\Couponables\Exceptions\InvalidCouponException;
use MichaelRubel\Couponables\Exceptions\NotAllowedToRedeemException;
use MichaelRubel\Couponables\Exceptions\OverLimitException;
use MichaelRubel\Couponables\Exceptions\OverQuantityException;
use MichaelRubel\Couponables\Models\Contracts\CouponContract;

interface CouponServiceContract
{
    /**
     * Get the coupon model by the code.
     *
     * @param  string|null  $code
     *
     * @return CouponContract|null
     */
    public function getCoupon(?string $code): ?CouponContract;

    /**
     * Verify if coupon is valid otherwise throw an exception.
     *
     * @param  string|null  $code
     * @param  Model|null  $redeemer
     *
     * @return CouponContract
     *
     * @throws CouponException
     * @throws OverLimitException
     * @throws OverQuantityException
     * @throws InvalidCouponException
     * @throws CouponExpiredException
     * @throws CouponDisabledException
     * @throws NotAllowedToRedeemException
     */
    public function verifyCoupon(?string $code, Model $redeemer = null): CouponContract;

    /**
     * Perform the stateless checks on the coupon
     * model. Redeemer is optional in this case.
     *
     * @param  CouponContract|null  $coupon
     * @param  Model|null  $redeemer
     *
     * @return CouponContract
     *
     * @throws CouponException
     * @throws OverQuantityException
     * @throws CouponExpiredException
     * @throws CouponDisabledException
     */
    public function performBasicChecksOn(?CouponContract $coupon, Model $redeemer = null): CouponContract;

    /**
     * Perform the "Redeemer" checks on the coupon model.
     *
     * @param  CouponContract|null  $coupon
     * @param  Model  $redeemer
     *
     * @return CouponContract
     *
     * @throws CouponException
     * @throws OverLimitException
     * @throws NotAllowedToRedeemException
     */
    public function performRedeemerChecksOn(?CouponContract $coupon, Model $redeemer): CouponContract;

    /**
     * Apply the coupon.
     *
     * @param  CouponContract  $coupon
     * @param  Model  $redeemer
     * @param  Model|null  $for
     *
     * @return CouponContract
     */
    public function applyCoupon(CouponContract $coupon, Model $redeemer, ?Model $for): CouponContract;
}
