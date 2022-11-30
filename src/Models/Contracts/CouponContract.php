<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Models\Contracts;

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

    /**
     * @return string
     */
    public function getCodeColumn(): string;

    /**
     * @return string
     */
    public function getTypeColumn(): string;

    /**
     * @return string
     */
    public function getValueColumn(): string;

    /**
     * @return string
     */
    public function getQuantityColumn(): string;

    /**
     * @return string
     */
    public function getLimitColumn(): string;

    /**
     * @return string
     */
    public function getExpiresAtColumn(): string;

    /**
     * @return string
     */
    public function getRedeemerTypeColumn(): string;

    /**
     * @return string
     */
    public function getRedeemerIdColumn(): string;

    /**
     * @return string
     */
    public function getDataColumn(): string;
}
