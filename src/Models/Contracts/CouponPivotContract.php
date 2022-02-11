<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Models\Contracts;

interface CouponPivotContract
{
    /**
     * @return string
     */
    public function getRedeemedAtColumn(): string;
}
