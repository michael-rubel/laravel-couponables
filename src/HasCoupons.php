<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use MichaelRubel\Couponables\Models\Contracts\CouponContract;
use MichaelRubel\Couponables\Models\Contracts\CouponPivotContract;
use MichaelRubel\Couponables\Services\Contracts\CouponServiceContract;
use MichaelRubel\EnhancedContainer\Call;
use MichaelRubel\EnhancedContainer\Core\CallProxy;

trait HasCoupons
{
    /**
     * @var CallProxy
     */
    protected static CallProxy $bindable;

    /**
     * @var CallProxy
     */
    protected static CallProxy $bindableCoupon;

    /**
     * @var CallProxy
     */
    protected static CallProxy $bindableService;

    /**
     * @var CallProxy
     */
    protected static CallProxy $bindablePivot;

    /**
     * Initialize the method binding objects.
     *
     * @return void
     */
    public function initializeHasCoupons(): void
    {
        self::$bindable        = call($this);
        self::$bindableCoupon  = call(CouponContract::class);
        self::$bindablePivot   = call(CouponPivotContract::class);
        self::$bindableService = call(CouponServiceContract::class);
    }

    /**
     * Polymorphic relation to the coupons.
     *
     * @return MorphToMany
     */
    public function coupons(): MorphToMany
    {
        return $this->morphToMany(
            self::$bindableCoupon->getInternal(Call::INSTANCE),
            Str::singular(config('couponables.pivot_table', 'couponables'))
        )->withPivot(self::$bindablePivot->getRedeemedAtColumn());
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
        return self::$bindableService->verifyCoupon($code, $this);
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
            callback: fn () => self::$bindable->verifyCoupon($code),
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
        $coupon = self::$bindableService->verifyCoupon($code, $this);

        return self::$bindableService->applyCoupon($coupon, $this);
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
            callback: fn () => self::$bindable->redeemCoupon($code),
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
        $column = self::$bindableCoupon->getCodeColumn();

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
        return self::$bindable->isCouponRedeemed($code);
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
        $coupon = self::$bindableService->getCoupon($code);

        return ! is_null($coupon) && call($coupon)->isOverLimitFor($this);
    }
}
