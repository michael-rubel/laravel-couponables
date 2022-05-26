<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Models\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MichaelRubel\Couponables\Models\Coupon;

trait DefinesPivotRelations
{
    /**
     * @return BelongsTo
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(config('couponables.model', Coupon::class));
    }
}
