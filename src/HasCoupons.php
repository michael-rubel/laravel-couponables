<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use MichaelRubel\Couponables\Models\Contracts\CouponContract;
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
    protected static CallProxy $bindableService;

    /**
     * Initialize the method binding objects.
     *
     * @return void
     */
    public function initializeHasCoupons(): void
    {
        self::$bindable        = call($this);
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
            self::$bindableService->model->getInternal(Call::INSTANCE),
            Str::singular(config('couponables.pivot_table', 'couponables'))
        )->withPivot(self::$bindableService->pivot->getRedeemedAtColumn());
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
     * @param Model|null  $redeemed
     *
     * @return CouponContract
     */
    public function redeemCoupon(?string $code, ?Model $redeemed = null): CouponContract
    {
        $coupon = self::$bindableService->verifyCoupon($code, $this);

        return self::$bindableService->applyCoupon($coupon, $this, $redeemed);
    }

    /**
     * Redeem the code using model.
     *
     * @param Model       $model
     * @param string|null $couponCode
     *
     * @return CouponContract
     */
    public function redeemBy(Model $model, ?string $couponCode): CouponContract
    {
        $coupon = self::$bindableService->verifyCoupon($couponCode, $model);

        return self::$bindableService->applyCoupon($coupon, $model, $this);
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
            callback: fn () => self::$bindable->redeemCoupon($code, null),
            rescue: $callback,
            report: $report
        );
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
        $column = self::$bindableService->model->getCodeColumn();

        return $this->coupons()
            ->where($column, $code)
            ->exists();
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
