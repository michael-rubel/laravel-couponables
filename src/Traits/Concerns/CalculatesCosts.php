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
        $discount = (float) $this->{static::getValueColumn()};

        if ($this->lessOrEqualZero($discount)) {
            throw new InvalidCouponValueException;
        }

        $result = match ($this->{static::getTypeColumn()}) {
            static::TYPE_SUBTRACTION, null => $this->subtract($using, $discount),
            static::TYPE_PERCENTAGE        => $this->percentage($using, $discount),
            static::TYPE_FIXED             => $this->fixedPrice($discount),
            default                        => throw new InvalidCouponTypeException,
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
    private function subtract(float $cost, float $discount): float
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
    private function percentage(float $value, float $discount): float
    {
        return (1.0 - ($discount / 100)) * $value;
    }

    /**
     * Apply the "Fixed Price" calculation strategy.
     *
     * @param  float  $discount
     * @return float
     */
    private function fixedPrice(float $discount): float
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
