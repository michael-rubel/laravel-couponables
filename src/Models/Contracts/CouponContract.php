<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Models\Contracts;

use Illuminate\Database\Eloquent\Model;

interface CouponContract
{
    /*
    | Functional definitions.
    |
    | You may implement the methods in the way you wish.
    */

    /**
     * The only model allowed to redeem the code if assigned.
     *
     * @return Model|null
     */
    public function redeemer(): ?Model;

    /**
     * Check if code is expired.
     *
     * @return bool
     */
    public function isExpired(): bool;

    /**
     * Check if code is not expired.
     *
     * @return bool
     */
    public function isNotExpired(): bool;

    /**
     * Check if code amount is over.
     *
     * @return bool
     */
    public function isOverQuantity(): bool;

    /**
     * Check if coupon is already redeemed by the model.
     *
     * @param Model $redeemer
     *
     * @return bool
     */
    public function isRedeemedBy(Model $redeemer): bool;

    /**
     * Check if the code is reached its limit for the passed model.
     *
     * @param Model $redeemer
     *
     * @return bool
     */
    public function isOverLimitFor(Model $redeemer): bool;

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
}
