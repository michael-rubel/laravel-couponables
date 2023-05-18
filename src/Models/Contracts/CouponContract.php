<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Models\Contracts;

use MichaelRubel\Couponables\Models\Coupon;

/**
 * @method Coupon|null firstWhere(string $column, string|null $value)
 *
 * @see Coupon
 */
interface CouponContract
{
    /*
    | Coupon type definitions.
    |
    | These keys are used to determine the calculation strategy.
    */

    /**
     * @var string
     */
    public const TYPE_SUBTRACTION = 'subtraction';

    /**
     * @var string
     */
    public const TYPE_PERCENTAGE = 'percentage';

    /**
     * @var string
     */
    public const TYPE_FIXED = 'fixed';

    /*
    | Column definitions.
    |
    | For package's internal purposes.
    */

    public function getCodeColumn(): string;

    public function getTypeColumn(): string;

    public function getValueColumn(): string;

    public function getQuantityColumn(): string;

    public function getLimitColumn(): string;

    public function getExpiresAtColumn(): string;

    public function getIsEnabledColumn(): string;

    public function getRedeemerTypeColumn(): string;

    public function getRedeemerIdColumn(): string;

    public function getDataColumn(): string;
}
