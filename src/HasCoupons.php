<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use MichaelRubel\Couponables\Exceptions\CouponException;
use MichaelRubel\Couponables\Models\Contracts\CouponContract;
use MichaelRubel\Couponables\Models\Contracts\CouponPivotContract;
use MichaelRubel\Couponables\Services\Contracts\CouponServiceContract;
use MichaelRubel\EnhancedContainer\Call;

trait HasCoupons
{
    /**
     * Polymorphic relation to the coupons.
     *
     * @return MorphToMany
     */
    public function coupons(): MorphToMany
    {
        return $this->morphToMany(app(CouponContract::class), Str::singular(
            config('couponables.pivot_table', 'couponables')
        ))->withPivot(
            call(CouponPivotContract::class)->getRedeemedAtColumn()
        );
    }

    /**
     * Perform coupon verification.
     *
     * @param string|null $code
     *
     * @return CouponContract
     */
    public function verifyCoupon(?string $code): CouponContract
    {
        $service = call(CouponServiceContract::class);
        $proxy   = call($service->verifyCoupon($code, $this));

        return $proxy->getInternal(Call::INSTANCE);
    }

    /**
     * Verify the coupon or do something else on fail.
     *
     * @param string|null $code
     * @param mixed|null $callback
     * @param bool $report
     *
     * @return mixed
     */
    public function verifyCouponOr(?string $code, mixed $callback = null, bool $report = false): mixed
    {
        return rescue(
            callback: fn () => call($this)->verifyCoupon($code),
            rescue: $callback,
            report: $report
        );
    }

    /**
     * Use the coupon.
     *
     * @param string|null $code
     *
     * @return CouponContract
     */
    public function redeemCoupon(?string $code): CouponContract
    {
        $service = call(CouponServiceContract::class);
        $proxy   = call($service->verifyCoupon($code, $this));

        $coupon = $proxy->getInternal(Call::INSTANCE);

        return $service->applyCoupon($coupon, $this);
    }

    /**
     * Redeem the coupon or do something else on fail.
     *
     * @param string|null $code
     * @param mixed|null $callback
     * @param bool $report
     *
     * @return mixed
     */
    public function redeemCouponOr(?string $code, mixed $callback = null, bool $report = false): mixed
    {
        return rescue(
            callback: fn () => call($this)->redeemCoupon($code),
            rescue: $callback,
            report: $report
        );
    }

    /**
     * Check if the coupon is already redeemed by the model at least once.
     *
     * @param string $code
     *
     * @return bool
     */
    public function isCouponRedeemed(string $code): bool
    {
        $column = call(CouponContract::class)
            ->getCodeColumn();

        return $this->coupons()
            ->where($column, $code)
            ->exists();
    }

    /**
     * Check if coupon with this code is already used.
     *
     * @param string|null $code
     *
     * @return bool
     */
    public function isCouponAlreadyUsed(?string $code): bool
    {
        return call($this)->isCouponRedeemed($code);
    }

    /**
     * Check if the coupon is over limit for the model.
     *
     * @param string|null $code
     *
     * @return bool
     *
     */
    public function isCouponOverLimit(?string $code): bool
    {
        $service = call(CouponServiceContract::class);
        $coupon  = $service->getCoupon($code);

        return ! is_null($coupon) && call($coupon)->isOverLimitFor($this);
    }
}
