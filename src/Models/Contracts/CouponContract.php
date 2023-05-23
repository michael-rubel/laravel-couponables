<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Models\Contracts;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use MichaelRubel\Couponables\Models\Coupon;

/**
 * @see Coupon
 *
 * @property string $code
 * @property string|null $type
 * @property string|null $value
 * @property bool $is_enabled
 * @property Collection|null $data
 * @property int|null $quantity
 * @property int|null $limit
 * @property string|null $redeemer_type
 * @property int|null $redeemer_id
 * @property CarbonInterface|null $expires_at
 *
 * @method Coupon|null firstWhere(string $column, string|null $value)
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

    public function getIsEnabledColumn(): string;

    public function getDataColumn(): string;

    public function getQuantityColumn(): string;

    public function getLimitColumn(): string;

    public function getRedeemerTypeColumn(): string;

    public function getRedeemerIdColumn(): string;

    public function getExpiresAtColumn(): string;
}
