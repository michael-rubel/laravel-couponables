<?php

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
}
