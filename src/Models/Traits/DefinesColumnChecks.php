<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Models\Traits;

use Illuminate\Database\Eloquent\Model;

trait DefinesColumnChecks
{
    /**
     * @return bool
     */
    public function isMorphColumnsFilled(): bool
    {
        return ! is_null($this->{static::getRedeemerTypeColumn()})
            && ! is_null($this->{static::getRedeemerIdColumn()});
    }

    /**
     * @return bool
     */
    public function isOnlyRedeemerTypeFilled(): bool
    {
        return ! is_null($this->{static::getRedeemerTypeColumn()})
            && is_null($this->{static::getRedeemerIdColumn()});
    }

    /**
     * @param  Model  $model
     *
     * @return bool
     */
    public function isSameRedeemerModel(Model $model): bool
    {
        return $this->{self::$bindable->getRedeemerTypeColumn()} === $model->getMorphClass();
    }
}
