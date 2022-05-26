<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Traits\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use MichaelRubel\Couponables\Models\Contracts\CouponContract;

trait GeneratesCoupons
{
    /**
     * Generate the coupon codes.
     *
     * @param int $times
     * @param int $length
     *
     * @return Collection
     */
    public function generateCouponCodes(int $times = 5, int $length = 7): Collection
    {
        return Collection::times($times, fn () => $this->model->create([
            $this->model->getCodeColumn() => Str::random($length),
        ]));
    }

    /**
     * Generate the coupon code to redeem only by the specified model.
     *
     * @param Model  $redeemer
     * @param string $code
     * @param array  $attributes
     *
     * @return CouponContract
     */
    public function generateCouponFor(Model $redeemer, string $code, array $attributes = []): CouponContract
    {
        $fields = collect([
            $this->model->getCodeColumn()         => $code,
            $this->model->getRedeemerTypeColumn() => $redeemer->getMorphClass(),
            $this->model->getRedeemerIdColumn()   => $redeemer->id,
        ])->merge($attributes)->toArray();

        return $this->model->create($fields);
    }
}
