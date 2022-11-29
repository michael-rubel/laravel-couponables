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
     * @param  float  $value
     *
     * @return float
     * @throws InvalidCouponTypeException
     * @throws InvalidCouponValueException
     */
    public function calc(float $value): float
    {
        $discount = (float) $this->{static::$bindable->getValueColumn()};

        if ($this->lessOrEqualsZero($discount)) {
            throw new InvalidCouponValueException;
        }

        $result = match ($this->{static::$bindable->getTypeColumn()}) {
            static::TYPE_SUBTRACTION => $this->subtract($value, $discount),
            static::TYPE_PERCENTAGE  => $this->percentage($value, $discount),
            static::TYPE_FIXED       => $this->fixedPrice($discount),
            default => throw new InvalidCouponTypeException,
        };

        return max(
            round($result, config('couponables.round') ?? 2),
            config('couponables.max') ?? 0
        );
    }

    /**
     * @param  float  $value
     * @return bool
     */
    private function lessOrEqualsZero(float $value): bool
    {
        return $value <= 0;
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
}
