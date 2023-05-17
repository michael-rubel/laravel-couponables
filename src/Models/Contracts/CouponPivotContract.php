<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Models\Contracts;

interface CouponPivotContract
{
    public function getRedeemedTypeColumn(): string;
    public function getRedeemedIdColumn(): string;
    public function getRedeemedAtColumn(): string;
    public function getCreatedAtColumn(): string;
}
