<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use MichaelRubel\Couponables\Models\Contracts\CouponPivotContract;

trait DefinesModelRelations
{
    /**
     * Fetch the latest couponables - redeemed coupon records.
     *
     * @return HasMany
     */
    public function couponables(): HasMany
    {
        $pivot = app(CouponPivotContract::class);

        return $this
            ->hasMany($pivot::class)
            ->orderBy($pivot->getRedeemedAtColumn(), 'desc');
    }

    /**
     * The only model allowed to redeem the coupon.
     *
     * @return MorphTo
     */
    public function redeemer(): MorphTo
    {
        return $this->morphTo();
    }
}
