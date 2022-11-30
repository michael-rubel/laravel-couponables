<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Traits\Concerns;

use MichaelRubel\Couponables\Exceptions\InvalidCouponTypeException;
use MichaelRubel\Couponables\Exceptions\InvalidCouponValueException;

trait CalculatesCosts
{
    /**
     * Calculate the output value based on the coupon type.
     *
     * @param  float  $using
     *
     * @return float
     * @throws InvalidCouponTypeException
     * @throws InvalidCouponValueException
     */
    public function calc(float $using): float
    {
        $discount = (float) $this->{static::$bindable->getValueColumn()};

        if ($this->lessOrEqualZero($discount)) {
            throw new InvalidCouponValueException;
        }

        $result = match ($this->{static::$bindable->getTypeColumn()}) {
            static::TYPE_SUBTRACTION => static::$bindable->subtract($using, $discount),
            static::TYPE_PERCENTAGE  => static::$bindable->percentage($using, $discount),
            static::TYPE_FIXED       => static::$bindable->fixedPrice($discount),
            default => throw new InvalidCouponTypeException,
        };

        $rounded = round($result,
            precision: config('couponables.round') ?? 2,
            mode: config('couponables.round_mode') ?? PHP_ROUND_HALF_UP
        );

        return max($rounded, config('couponables.max') ?? 0);
    }

    /**
     * Apply the "Subtraction" calculation strategy.
     *
     * @param  float  $cost
     * @param  float  $discount
     *
     * @return float
     */
    public function subtract(float $cost, float $discount): float
    {
        return $cost - $discount;
    }

    /**
     * Apply the "Percentage" calculation strategy.
     *
     * @param  float  $value
     * @param  float  $discount
     *
     * @return float
     */
    public function percentage(float $value, float $discount): float
    {
        return (1.0 - ($discount / 100)) * $value;
    }

    /**
     * Apply the "Fixed Price" calculation strategy.
     *
     * @param  float  $discount
     * @return float
     */
    public function fixedPrice(float $discount): float
    {
        return $discount;
    }

    /**
     * @param  float  $value
     * @return bool
     */
    private function lessOrEqualZero(float $value): bool
    {
        return $value <= 0;
    }
}
