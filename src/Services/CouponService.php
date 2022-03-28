<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Services;

use Illuminate\Database\Eloquent\Model;
use MichaelRubel\Couponables\Events\CouponRedeemed;
use MichaelRubel\Couponables\Exceptions\CouponExpiredException;
use MichaelRubel\Couponables\Exceptions\InvalidCouponException;
use MichaelRubel\Couponables\Exceptions\NotAllowedToRedeemException;
use MichaelRubel\Couponables\Exceptions\OverLimitException;
use MichaelRubel\Couponables\Exceptions\OverQuantityException;
use MichaelRubel\Couponables\Models\Contracts\CouponContract;
use MichaelRubel\Couponables\Models\Contracts\CouponPivotContract;
use MichaelRubel\Couponables\Services\Contracts\CouponServiceContract;
use MichaelRubel\EnhancedContainer\Call;
use MichaelRubel\EnhancedContainer\Core\CallProxy;

class CouponService implements CouponServiceContract
{
    /**
     * @var CallProxy
     */
    protected CallProxy $model;

    /**
     * @var CallProxy
     */
    protected CallProxy $pivot;

    /**
     * @param CouponContract      $model
     * @param CouponPivotContract $pivot
     */
    public function __construct(CouponContract $model, CouponPivotContract $pivot)
    {
        $this->model = call($model);
        $this->pivot = call($pivot);
    }

    /**
     * Get the coupon model by the code.
     *
     * @param string|null $code
     *
     * @return CouponContract|null
     */
    public function getCoupon(?string $code): ?CouponContract
    {
        return $this->model
            ->where($this->model->getCodeColumn(), $code)
            ->first();
    }

    /**
     * Verify if coupon is valid otherwise throw an exception.
     *
     * @param string|null $code
     * @param Model       $redeemer
     *
     * @return CouponContract
     * @throws OverQuantityException
     * @throws OverLimitException
     * @throws NotAllowedToRedeemException
     * @throws CouponExpiredException
     * @throws InvalidCouponException
     */
    public function verifyCoupon(?string $code, Model $redeemer): CouponContract
    {
        $coupon = call($this->getCoupon($code) ?? throw new InvalidCouponException);

        if ($coupon->isExpired()) {
            throw new CouponExpiredException;
        }

        if ($coupon->isOverQuantity()) {
            throw new OverQuantityException;
        }

        if ($this->isOverLimit($coupon, $redeemer, $code)) {
            throw new OverLimitException;
        }

        if ($coupon->isMorphColumnsFilled() && ! $coupon->redeemer()?->is($redeemer)) {
            throw new NotAllowedToRedeemException;
        }

        return $coupon->getInternal(Call::INSTANCE);
    }

    /**
     * Apply the coupon.
     *
     * @param CouponContract $coupon
     * @param Model          $redeemer
     *
     * @return CouponContract
     */
    public function applyCoupon(CouponContract $coupon, Model $redeemer): CouponContract
    {
        $redeemer->coupons()->attach($coupon, [
            $this->pivot->getRedeemedAtColumn() => now(),
        ]);

        if (! is_null($coupon->{$this->model->getQuantityColumn()})) {
            $coupon->decrement($this->model->getQuantityColumn());
        }

        event(new CouponRedeemed($this, $coupon));

        return $coupon;
    }

    /**
     * @param mixed       $coupon
     * @param Model       $redeemer
     * @param string|null $code
     *
     * @return bool
     */
    protected function isOverLimit(mixed $coupon, Model $redeemer, ?string $code): bool
    {
        return ($coupon->isDisposable() && call($redeemer)->isCouponRedeemed($code))
            || $coupon->isOverLimitFor($redeemer);
    }
}
