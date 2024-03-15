<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Traits;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use MichaelRubel\Couponables\Models\Contracts\CouponContract;
use MichaelRubel\Couponables\Services\Contracts\CouponServiceContract;
use Throwable;

trait HasCoupons
{
    /**
     * @var CouponServiceContract
     */
    protected CouponServiceContract $couponService;

    /**
     * Initialize the method binding objects.
     *
     * @return void
     */
    protected function initializeHasCoupons(): void
    {
        if (app()->bound(CouponServiceContract::class)) {
            $this->couponService = app(CouponServiceContract::class);
        }
    }

    /**
     * Polymorphic relation to the coupons.
     *
     * @return MorphToMany
     */
    public function coupons(): MorphToMany
    {
        return with($this->couponService, function (CouponServiceContract $service) {
            $morphName = Str::singular(config('couponables.pivot_table', 'couponables'));

            return $this->morphToMany($service->model, $morphName)->withPivot([
                $service->pivot->getRedeemedTypeColumn(),
                $service->pivot->getRedeemedIdColumn(),
                $service->pivot->getRedeemedAtColumn(),
                $service->pivot->getCreatedAtColumn(),
                $service->pivot->getUpdatedAtColumn(),
            ]);
        });
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
        $column = $this->couponService->model->getCodeColumn();

        return $this->coupons()->where($column, $code)->exists();
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
        $coupon = $this->couponService->getCoupon($code);

        return ! is_null($coupon) && $coupon->isOverLimitFor($this);
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
        return $this->couponService->verifyCoupon($code, $this);
    }

    /**
     * Verify and use the coupon.
     *
     * @param  string|null  $code
     * @param  Model|null  $for
     *
     * @return CouponContract
     */
    public function redeemCoupon(?string $code, ?Model $for = null): CouponContract
    {
        $coupon = $this->couponService->verifyCoupon($code, $this);

        return $this->couponService->applyCoupon($coupon, $this, $for);
    }

    /**
     * Verify the coupon or do something else on fail.
     *
     * @param  string|null  $code
     * @param  Closure|null  $callback
     *
     * @return mixed
     */
    public function verifyCouponOr(?string $code, ?Closure $callback = null): mixed
    {
        try {
            return $this->verifyCoupon($code);
        } catch (Throwable $e) {
            return $callback instanceof Closure ? $callback($code, $e) : throw $e;
        }
    }

    /**
     * Redeem the coupon or do something else on fail.
     *
     * @param  string|null  $code
     * @param  Closure|null  $callback
     *
     * @return mixed
     */
    public function redeemCouponOr(?string $code, ?Closure $callback = null): mixed
    {
        try {
            return $this->redeemCoupon($code);
        } catch (Throwable $e) {
            return $callback instanceof Closure ? $callback($code, $e) : throw $e;
        }
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
        $coupon = $this->couponService->verifyCoupon($couponCode, $model);

        return $this->couponService->applyCoupon($coupon, $model, $this);
    }
}
