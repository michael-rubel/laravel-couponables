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
}
