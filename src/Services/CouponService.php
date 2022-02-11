<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Services;

use Illuminate\Database\Eloquent\Model;
use MichaelRubel\Couponables\Exceptions\OverLimitException;
use MichaelRubel\Couponables\Exceptions\InvalidCouponException;
use MichaelRubel\Couponables\Exceptions\NotAllowedToRedeemException;
use MichaelRubel\Couponables\Exceptions\OverQuantityException;
use MichaelRubel\Couponables\Exceptions\CouponExpiredException;
use MichaelRubel\Couponables\Models\Contracts\CouponContract;
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
     * @param CouponContract $model
     */
    public function __construct(CouponContract $model)
    {
        $this->model = call($model);
    }

    /**
     * Verify if promotional code is valid otherwise throw an exception.
     *
     * @param string $code
     * @param Model  $redeemer
     *
     * @return CouponContract
     * @throws OverQuantityException
     * @throws OverLimitException
     * @throws NotAllowedToRedeemException
     * @throws CouponExpiredException
     * @throws InvalidCouponException
     */
    public function verifyCoupon(string $code, Model $redeemer): CouponContract
    {
        $coupon = $this->model
            ->where($this->model->getCodeColumn(), $code)
            ->firstOr(fn () => throw new InvalidCouponException);

        $coupon = call($coupon);

        if ($coupon->isExpired()) {
            throw new CouponExpiredException;
        }

        if ($coupon->isOverQuantity()) {
            throw new OverQuantityException;
        }

        if ($coupon->isOverLimitFor($redeemer)) {
            throw new OverLimitException;
        }

        if ($coupon->isMorphColumnsFilled() && ! $coupon->redeemer()?->is($redeemer)) {
            throw new NotAllowedToRedeemException;
        }

        return $coupon->getInternal(Call::INSTANCE);
    }
}
