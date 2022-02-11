<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use MichaelRubel\Couponables\Models\Contracts\CouponContract;
use MichaelRubel\Couponables\Models\Traits\DefinesColumns;

class Coupon extends Model implements CouponContract
{
    use HasFactory, DefinesColumns;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'code'     => 'string',
        'data'     => 'collection',
        'quantity' => 'integer',
        'limit'    => 'integer',
        'datetime' => 'datetime',
    ];

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('couponables.table', 'coupons');
    }

    /**
     * @return Model|null
     */
    public function redeemer(): ?Model
    {
        return $this->isMorphColumnsFilled()
            ? $this->morphTo()->first()
            : null;
    }

    /**
     * Check if code is expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        $expires_at = $this->{$this->getExpiresAtColumn()};

        return $expires_at && now()->gte($expires_at);
    }

    /**
     * Check if code is not expired.
     *
     * @return bool
     */
    public function isNotExpired(): bool
    {
        return ! $this->isExpired();
    }

    /**
     * Check if code amount is over.
     *
     * @return bool
     */
    public function isOverQuantity(): bool
    {
        $quantity = $this->{$this->getQuantityColumn()};

        return ! is_null($quantity) && $quantity <= 0;
    }

    /**
     * Check if code is for one-time use.
     *
     * @param Model $redeemer
     *
     * @return bool
     */
    public function isOverLimitFor(Model $redeemer): bool
    {
        return $this->{$this->getLimitColumn()} <= $redeemer->coupons()->count();
    }
}
