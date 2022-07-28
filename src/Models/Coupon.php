<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use MichaelRubel\Couponables\Models\Contracts\CouponContract;
use MichaelRubel\Couponables\Models\Traits\DefinesColumnChecks;
use MichaelRubel\Couponables\Models\Traits\DefinesColumns;
use MichaelRubel\Couponables\Models\Traits\DefinesModelRelations;
use MichaelRubel\EnhancedContainer\Core\CallProxy;

class Coupon extends Model implements CouponContract
{
    use HasFactory,
        DefinesColumns,
        DefinesColumnChecks,
        DefinesModelRelations;

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
        'type'     => 'string',
        'data'     => 'collection',
        'quantity' => 'integer',
        'limit'    => 'integer',
        'datetime' => 'datetime',
    ];

    /**
     * @var CallProxy
     */
    protected static CallProxy $bindable;

    /**
     * @param  array  $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('couponables.table', 'coupons');

        static::$bindable = call($this);
    }

    /**
     * Check if code is expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        $expires_at = $this->{static::$bindable->getExpiresAtColumn()};

        return $expires_at && now()->gte($expires_at);
    }

    /**
     * Check if code is not expired.
     *
     * @return bool
     */
    public function isNotExpired(): bool
    {
        return ! static::$bindable->isExpired();
    }

    /**
     * Check if code amount is over.
     *
     * @return bool
     */
    public function isOverQuantity(): bool
    {
        $quantity = $this->{static::$bindable->getQuantityColumn()};

        return ! is_null($quantity) && $quantity <= 0;
    }

    /**
     * Check if coupon is disposable.
     *
     * @return bool
     */
    public function isDisposable(): bool
    {
        $limit = $this->{static::$bindable->getLimitColumn()};

        return ! is_null($limit) && $limit == 1;
    }

    /**
     * Check if the code is reached its global limit.
     *
     * @param  Model  $redeemer
     * @param  string|null  $code
     *
     * @return bool
     */
    public function isOverLimit(Model $redeemer, ?string $code): bool
    {
        return (static::$bindable->isDisposable() && call($redeemer)->isCouponAlreadyUsed($code))
            || static::$bindable->isOverLimitFor($redeemer);
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
        $column = static::$bindable->getCodeColumn();
        $limit  = $this->{static::$bindable->getLimitColumn()};

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
        $column = static::$bindable->getCodeColumn();

        return ! is_null($this->{$column}) && $redeemer
            ->coupons()
            ->where($column, $this->{$column})
            ->exists();
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
        return with(static::$bindable, function ($coupon) use ($redeemer) {
            if ($coupon->isMorphColumnsFilled() && ! $coupon->redeemer()?->is($redeemer)) {
                return false;
            }

            if ($coupon->isOnlyRedeemerTypeFilled() && ! $coupon->isSameRedeemerModel($redeemer)) {
                return false;
            }

            return true;
        });
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
}
