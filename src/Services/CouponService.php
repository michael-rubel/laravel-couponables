<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\Macroable;
use MichaelRubel\Couponables\Events\CouponDisabled;
use MichaelRubel\Couponables\Events\CouponExpired;
use MichaelRubel\Couponables\Events\CouponIsOverLimit;
use MichaelRubel\Couponables\Events\CouponIsOverQuantity;
use MichaelRubel\Couponables\Events\CouponRedeemed;
use MichaelRubel\Couponables\Events\CouponVerified;
use MichaelRubel\Couponables\Events\NotAllowedToRedeem;
use MichaelRubel\Couponables\Exceptions\CouponDisabledException;
use MichaelRubel\Couponables\Exceptions\CouponExpiredException;
use MichaelRubel\Couponables\Exceptions\InvalidCouponException;
use MichaelRubel\Couponables\Exceptions\NotAllowedToRedeemException;
use MichaelRubel\Couponables\Exceptions\OverLimitException;
use MichaelRubel\Couponables\Exceptions\OverQuantityException;
use MichaelRubel\Couponables\Models\Contracts\CouponContract;
use MichaelRubel\Couponables\Models\Contracts\CouponPivotContract;
use MichaelRubel\Couponables\Services\Contracts\CouponServiceContract;
use MichaelRubel\Couponables\Traits\Concerns\GeneratesCoupons;

class CouponService implements CouponServiceContract
{
    use Macroable, GeneratesCoupons;

    /**
     * @param  CouponContract  $model
     * @param  CouponPivotContract  $pivot
     */
    public function __construct(
        public CouponContract $model,
        public CouponPivotContract $pivot,
    ) {
    }

    /**
     * Get the coupon model by the code.
     *
     * @param  string|null  $code
     *
     * @return CouponContract|null
     */
    public function getCoupon(?string $code): ?CouponContract
    {
        return $this->model->firstWhere($this->model->getCodeColumn(), $code);
    }

    /**
     * Perform the stateless checks on the coupon
     * model. Redeemer is optional in this case.
     *
     * @param  CouponContract  $coupon
     * @param  Model|null  $redeemer
     *
     * @return CouponContract
     *
     * @throws CouponExpiredException
     * @throws OverQuantityException
     * @throws CouponDisabledException
     */
    public function performBasicChecksOn(CouponContract $coupon, ?Model $redeemer = null): CouponContract
    {
        if ($coupon->isDisabled()) {
            event(new CouponDisabled($coupon, $redeemer));

            throw new CouponDisabledException;
        }

        if ($coupon->isExpired()) {
            event(new CouponExpired($coupon, $redeemer));

            throw new CouponExpiredException;
        }

        if ($coupon->isOverQuantity()) {
            event(new CouponIsOverQuantity($coupon, $redeemer));

            throw new OverQuantityException;
        }

        return $coupon;
    }

    /**
     * Perform the "Redeemer" checks on the coupon model.
     *
     * @param  CouponContract  $coupon
     * @param  Model  $redeemer
     *
     * @return CouponContract
     *
     * @throws OverLimitException
     * @throws NotAllowedToRedeemException
     */
    public function performRedeemerChecksOn(CouponContract $coupon, Model $redeemer): CouponContract
    {
        if (! $coupon->isAllowedToRedeemBy($redeemer)) {
            event(new NotAllowedToRedeem($coupon, $redeemer));

            throw new NotAllowedToRedeemException;
        }

        if ($coupon->isOverLimit($redeemer)) {
            event(new CouponIsOverLimit($coupon, $redeemer));

            throw new OverLimitException;
        }

        return $coupon;
    }

    /**
     * Verify if coupon is valid otherwise throw an exception.
     *
     * @param  string|null  $code
     * @param  Model|null  $redeemer
     *
     * @return CouponContract
     * @throws CouponExpiredException
     * @throws InvalidCouponException
     * @throws NotAllowedToRedeemException
     * @throws OverLimitException
     * @throws OverQuantityException
     * @throws CouponDisabledException
     */
    public function verifyCoupon(?string $code, ?Model $redeemer = null): CouponContract
    {
        $coupon = $this->getCoupon($code) ?? throw new InvalidCouponException;

        $this->performBasicChecksOn($coupon);

        if ($redeemer) {
            $this->performRedeemerChecksOn($coupon, $redeemer);
        }

        event(new CouponVerified($coupon, $redeemer));

        return $coupon;
    }

    /**
     * Apply the coupon.
     *
     * @param  CouponContract  $coupon
     * @param  Model  $redeemer
     * @param  Model|null  $for
     *
     * @return CouponContract
     */
    public function applyCoupon(CouponContract $coupon, Model $redeemer, ?Model $for): CouponContract
    {
        $redeemer->coupons()->attach($coupon, [
            $this->pivot->getRedeemedTypeColumn() => $for?->getMorphClass(),
            $this->pivot->getRedeemedIdColumn()   => $for?->id,
            $this->pivot->getRedeemedAtColumn()   => now(),
            $this->pivot->getCreatedAtColumn()    => now(),
        ]);

        if (! is_null($coupon->{$this->model->getQuantityColumn()})) {
            $coupon->decrement($this->model->getQuantityColumn());
        }

        event(new CouponRedeemed($coupon, $redeemer));

        return $coupon;
    }
}
