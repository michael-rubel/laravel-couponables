<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use MichaelRubel\Couponables\Models\Contracts\CouponContract;
use MichaelRubel\Couponables\Models\Contracts\CouponPivotContract;
use MichaelRubel\Couponables\Models\Coupon;
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
        return $this->morphToMany(
            config('couponables.model', Coupon::class),
            Str::singular(config('couponables.pivot_table', 'couponables'))
        )->withPivot(
            call(CouponPivotContract::class)->getRedeemedAtColumn()
        );
    }

    /**
     * Use the coupon.
     *
     * @param string $code
     *
     * @return CouponContract
     */
    public function redeemCoupon(string $code): CouponContract
    {
        $service = call(CouponServiceContract::class);
        $proxy   = call($service->verifyCoupon($code, $this));

        $coupon = $proxy->getInternal(Call::INSTANCE);

        $service->applyCoupon($coupon, $this);

        return $coupon;
    }

    /**
     * @alias redeemCoupon()
     *
     * @param string $code
     *
     * @return CouponContract
     */
    public function applyCoupon(string $code): CouponContract
    {
        return $this->redeemCoupon($code);
    }
}
