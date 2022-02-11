<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Models;

use Illuminate\Database\Eloquent\Relations\MorphPivot;
use MichaelRubel\Couponables\Models\Contracts\CouponPivotContract;
use MichaelRubel\Couponables\Models\Traits\DefinesPivotColumns;

class Couponable extends MorphPivot implements CouponPivotContract
{
    use DefinesPivotColumns;

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('couponables.pivot_table', 'couponables');
    }
}
