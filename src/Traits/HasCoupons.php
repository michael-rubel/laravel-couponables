<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Traits;

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
        self::$bindable = call($this);
        self::$bindableService = call(CouponServiceContract::class);
    }

    /**
     * Polymorphic relation to the coupons.
     *
     * @return MorphToMany
     */
    public function coupons(): MorphToMany
    {
        return with(self::$bindableService, fn ($service) => $this->morphToMany(
            $service->model->getInternal(Call::INSTANCE),
            Str::singular(config('couponables.pivot_table', 'couponables'))
        )->withPivot([
            $service->pivot->getRedeemedTypeColumn(),
            $service->pivot->getRedeemedIdColumn(),
            $service->pivot->getRedeemedAtColumn(),
            $service->pivot->getCreatedAtColumn(),
            $service->pivot->getUpdatedAtColumn(),
        ]));
    }

    /**
     * Check if coupon with this code is already used.
     *
     * @param  string|null  $code
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
     * @param  string|null  $code
     *
     * @return bool
     */
    public function isCouponOverLimit(?string $code): bool
    {
        $coupon = self::$bindableService->getCoupon($code);

        return ! is_null($coupon) && call($coupon)->isOverLimitFor($this);
    }

    /**
     * Verify if the coupon is valid.
     *
     * @param  string|null  $code
     *
     * @return CouponContract
     */
    public function verifyCoupon(?string $code): CouponContract
    {
        return self::$bindableService->verifyCoupon($code, $this);
    }

    /**
     * Verify and use the coupon.
     *
     * @param  string|null  $code
     * @param  Model|null  $redeemed
     *
     * @return CouponContract
     */
    public function redeemCoupon(?string $code, ?Model $redeemed = null): CouponContract
    {
        return with(self::$bindableService, function ($service) use ($code, $redeemed) {
            $coupon = $service->verifyCoupon($code, $this);

            return $service->applyCoupon($coupon, $this, $redeemed);
        });
    }

    /**
     * Verify the coupon or do something else on fail.
     *
     * @param  string|null  $code
     * @param  mixed|null  $callback
     *
     * @return mixed
     */
    public function verifyCouponOr(?string $code, mixed $callback = null): mixed
    {
        return rescue(
            fn () => self::$bindable->verifyCoupon($code),
            $callback,
            false
        );
    }

    /**
     * Redeem the coupon or do something else on fail.
     *
     * @param  string|null  $code
     * @param  mixed|null  $callback
     *
     * @return mixed
     */
    public function redeemCouponOr(?string $code, mixed $callback = null): mixed
    {
        return rescue(
            fn () => self::$bindable->redeemCoupon($code, null),
            $callback,
            false
        );
    }

    /**
     * Redeem the code using model.
     *
     * @param  Model  $model
     * @param  string|null  $couponCode
     *
     * @return CouponContract
     */
    public function redeemBy(Model $model, ?string $couponCode): CouponContract
    {
        return with(self::$bindableService, function ($service) use ($couponCode, $model) {
            $coupon = $service->verifyCoupon($couponCode, $model);

            return $service->applyCoupon($coupon, $model, $this);
        });
    }
}
