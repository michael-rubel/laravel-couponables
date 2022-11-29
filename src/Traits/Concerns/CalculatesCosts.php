<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Traits\Concerns;

use MichaelRubel\Couponables\Exceptions\InvalidCouponTypeException;

trait CalculatesCosts
{
    /**
     * @param  float  $ofValue
     *
     * @return float
     * @throws InvalidCouponTypeException
     */
    public function calcByType(float $ofValue): float
    {
        return match ($this->{static::$bindable->getTypeColumn()}) {
            $this::TYPE_SUBTRACTION => $this->subtract($ofValue),
            $this::TYPE_PERCENTAGE  => $this->percentage($ofValue),
            $this::TYPE_FIXED       => $this->fixedPrice(),
            default => throw new InvalidCouponTypeException,
        };
    }

    /**
     * @param  float  $cost
     *
     * @return float
     */
    private function subtract(float $cost): float
    {
        return $cost - $this->{static::$bindable->getValueColumn()};
    }

    /**
     * @param  float  $of
     *
     * @return float
     */
    private function percentage(float $of): float
    {
        return ($this->{static::$bindable->getValueColumn()} / 100) * $of;
    }

    /**
     * @return float
     */
    private function fixedPrice(): float
    {
        return (float) $this->{static::$bindable->getValueColumn()};
    }
}
