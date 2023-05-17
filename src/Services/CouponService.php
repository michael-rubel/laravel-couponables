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
use MichaelRubel\Couponables\Events\FailedToRedeemCoupon;
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
use MichaelRubel\EnhancedContainer\Call;
use MichaelRubel\EnhancedContainer\Core\CallProxy;
use Throwable;

class CouponService implements CouponServiceContract
{
    use Macroable, GeneratesCoupons;

    /**
     * @var CallProxy
     */
    public CallProxy $service;

    /**
     * @var CallProxy
     */
    public CallProxy $model;

    /**
     * @var CallProxy
     */
    public CallProxy $pivot;

    /**
     * @param  CouponContract  $model
     * @param  CouponPivotContract  $pivot
     */
    public function __construct(CouponContract $model, CouponPivotContract $pivot)
    {
        $this->service = call($this);
        $this->model   = call($model);
        $this->pivot   = call($pivot);
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
    public function performBasicChecks(CouponContract $coupon, ?Model $redeemer = null): CouponContract
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
        $coupon = call($this->getCoupon($code) ?? throw new InvalidCouponException);

        $instance = $coupon->getInternal(Call::INSTANCE);

        /**
         * @var CouponContract $coupon
         */

        $this->performBasicChecks($instance);

        if (! $coupon->isAllowedToRedeemBy($redeemer)) {
            event(new NotAllowedToRedeem($instance, $redeemer));

            throw new NotAllowedToRedeemException;
        }

        if ($coupon->isOverLimit($redeemer, $code)) {
            event(new CouponIsOverLimit($instance, $redeemer));

            throw new OverLimitException;
        }

        event(new CouponVerified($instance, $redeemer));

        return $instance;
    }

    /**
     * Apply the coupon.
     *
     * @param  CouponContract  $coupon
     * @param  Model  $redeemer
     * @param  Model|null  $redeemed
     *
     * @return CouponContract
     */
    public function applyCoupon(CouponContract $coupon, Model $redeemer, ?Model $redeemed): CouponContract
    {
        try {
            call($redeemer)->coupons()->attach($coupon, [
                $this->pivot->getRedeemedTypeColumn() => $redeemed?->getMorphClass(),
                $this->pivot->getRedeemedIdColumn()   => $redeemed?->id,
                $this->pivot->getRedeemedAtColumn()   => now(),
                $this->pivot->getCreatedAtColumn()    => now(),
            ]);

            if (! is_null($coupon->{$this->model->getQuantityColumn()})) {
                $coupon->decrement($this->model->getQuantityColumn());
            }
        } catch (Throwable $e) {
            event(new FailedToRedeemCoupon($coupon, $redeemer, $redeemed));

            throw $e;
        }

        event(new CouponRedeemed($coupon, $redeemer));

        return $coupon;
    }
}
