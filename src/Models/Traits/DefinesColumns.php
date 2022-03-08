<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Models\Traits;

trait DefinesColumns
{
    /**
     * @return string
     */
    public function getCodeColumn(): string
    {
        return 'code';
    }

    /**
     * @return string
     */
    public function getTypeColumn(): string
    {
        return 'type';
    }

    /**
     * @return string
     */
    public function getQuantityColumn(): string
    {
        return 'quantity';
    }

    /**
     * @return string
     */
    public function getLimitColumn(): string
    {
        return 'limit';
    }

    /**
     * @return string
     */
    public function getExpiresAtColumn(): string
    {
        return 'expires_at';
    }

    /**
     * @return string
     */
    public function getRedeemerTypeColumn(): string
    {
        return 'redeemer_type';
    }

    /**
     * @return string
     */
    public function getRedeemerIdColumn(): string
    {
        return 'redeemer_id';
    }

    /**
     * @return bool
     */
    public function isMorphColumnsFilled(): bool
    {
        return ! is_null($this->{$this->getRedeemerTypeColumn()})
            && ! is_null($this->{$this->getRedeemerIdColumn()});
    }
}
