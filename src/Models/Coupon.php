<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use MichaelRubel\Couponables\Models\Contracts\CouponContract;
use MichaelRubel\Couponables\Models\Factories\CouponFactory;
use MichaelRubel\Couponables\Models\Traits\DefinesColumnChecks;
use MichaelRubel\Couponables\Models\Traits\DefinesColumns;
use MichaelRubel\Couponables\Models\Traits\DefinesModelRelations;
use MichaelRubel\Couponables\Traits\Concerns\CalculatesCosts;

class Coupon extends Model implements CouponContract
{
    use HasFactory;
    use DefinesColumns;
    use DefinesColumnChecks;
    use DefinesModelRelations;
    use CalculatesCosts;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'code'       => 'string',
        'type'       => 'string',
        'value'      => 'string',
        'is_enabled' => 'boolean',
        'data'       => 'collection',
        'quantity'   => 'integer',
        'limit'      => 'integer',
        'expires_at' => 'datetime',
    ];

    /**
     * @param  array  $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('couponables.table', 'coupons');
    }

    /**
     * Check if code is expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        $expires_at = $this->{static::getExpiresAtColumn()};

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
     * Check if code is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->{static::getIsEnabledColumn()} ?? true;
    }

    /**
     * Check if code is disabled.
     *
     * @return bool
     */
    public function isDisabled(): bool
    {
        return ! $this->isEnabled();
    }

    /**
     * Check if code amount is over.
     *
     * @return bool
     */
    public function isOverQuantity(): bool
    {
        $quantity = $this->{static::getQuantityColumn()};

        return ! is_null($quantity) && $quantity <= 0;
    }

    /**
     * Check if coupon is disposable.
     *
     * @return bool
     */
    public function isDisposable(): bool
    {
        return $this->{static::getLimitColumn()} == 1;
    }

    /**
     * Check if the code is reached its global limit.
     *
     * @param  Model  $redeemer
     *
     * @return bool
     */
    public function isOverLimit(Model $redeemer): bool
    {
        return ($this->isDisposable() && $redeemer->isCouponAlreadyUsed($this->{static::getCodeColumn()}))
            || $this->isOverLimitFor($redeemer);
    }

    /**
     * Check if the code is reached its limit for the passed model.
     *
     * @param  Model  $redeemer
     *
     * @return bool
     */
    public function isOverLimitFor(Model $redeemer): bool
    {
        $column = static::getCodeColumn();
        $limit  = $this->{static::getLimitColumn()};

        return ! is_null($limit) && $limit <= $redeemer
            ->coupons()
            ->where($column, $this->{$column})
            ->count();
    }

    /**
     * Check if coupon is already redeemed by the model.
     *
     * @param  Model  $redeemer
     *
     * @return bool
     */
    public function isRedeemedBy(Model $redeemer): bool
    {
        $column = static::getCodeColumn();

        return $redeemer->coupons()->where($column, $this->{$column})->exists();
    }

    /**
     * Check if the model is allowed to redeem.
     *
     * @param  Model  $redeemer
     *
     * @return bool
     */
    public function isAllowedToRedeemBy(Model $redeemer): bool
    {
        if ($this->isMorphColumnsFilled() && ! $this->redeemer?->is($redeemer)) {
            return false;
        }

        if ($this->isOnlyRedeemerTypeFilled() && ! $this->isSameRedeemerModel($redeemer)) {
            return false;
        }

        return true;
    }

    /**
     * Assign the model to the latest redeemed coupon.
     *
     * @param  Model  $redeemed
     *
     * @return CouponContract
     */
    public function for(Model $redeemed): CouponContract
    {
        return with($this->couponables()->first(), function ($couponable) use ($redeemed) {
            $morphValues = transform($couponable, fn ($bindable) => [
                $bindable->getRedeemedTypeColumn() => $redeemed->getMorphClass(),
                $bindable->getRedeemedIdColumn()   => $redeemed->id,
            ]);

            $couponable->update($morphValues);

            return $this;
        });
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory<Coupon>
     */
    protected static function newFactory(): Factory
    {
        return CouponFactory::new();
    }
}
