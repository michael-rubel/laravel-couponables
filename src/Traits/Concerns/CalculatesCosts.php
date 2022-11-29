<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Traits\Concerns;

use MichaelRubel\Couponables\Exceptions\InvalidCouponTypeException;

trait CalculatesCosts
{
    /**
     * Calculate the output value based on the coupon type.
     *
     * @param  float  $value
     *
     * @return float
     * @throws InvalidCouponTypeException
     */
    public function calc(float $value): float
    {
        return match ($this->{static::$bindable->getTypeColumn()}) {
            static::TYPE_SUBTRACTION => $this->subtract($value),
            static::TYPE_PERCENTAGE  => $this->percentage($value),
            static::TYPE_FIXED       => $this->fixedPrice(),
            default => throw new InvalidCouponTypeException,
        };
    }

    /**
     * Apply the "Subtraction" calculation strategy.
     *
     * @param  float  $cost
     *
     * @return float
     */
    private function subtract(float $cost): float
    {
        return $cost - $this->{static::$bindable->getValueColumn()};
    }

    /**
     * Apply the "Percentage" calculation strategy.
     *
     * @param  float  $value
     *
     * @return float
     */
    private function percentage(float $value): float
    {
        return ($this->{static::$bindable->getValueColumn()} / 100) * $value;
    }

    /**
     * Apply the "Fixed Price" calculation strategy.
     *
     * @return float
     */
    private function fixedPrice(): float
    {
        return (float) $this->{static::$bindable->getValueColumn()};
    }
}
