<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Models\Traits;

trait DefinesPivotColumns
{
    /**
     * @return string
     */
    public function getRedeemedAtColumn(): string
    {
        return 'redeemed_at';
    }

    /**
     * @return string
     */
    public function getRedeemedTypeColumn(): string
    {
        return 'redeemed_type';
    }

    /**
     * @return string
     */
    public function getRedeemedIdColumn(): string
    {
        return 'redeemed_id';
    }

    /**
     * @return string
     */
    public function getCreatedAtColumn(): string
    {
        return 'created_at';
    }

    /**
     * @return string
     */
    public function getUpdatedAtColumn(): string
    {
        return 'updated_at';
    }
}
