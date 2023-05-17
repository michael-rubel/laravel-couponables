<?php

declare(strict_types=1);

namespace MichaelRubel\Couponables\Models\Traits;

trait DefinesColumns
{
    public function getCodeColumn(): string
    {
        return 'code';
    }

    public function getTypeColumn(): string
    {
        return 'type';
    }

    public function getValueColumn(): string
    {
        return 'value';
    }

    public function getQuantityColumn(): string
    {
        return 'quantity';
    }

    public function getLimitColumn(): string
    {
        return 'limit';
    }

    public function getExpiresAtColumn(): string
    {
        return 'expires_at';
    }

    public function getIsEnabledColumn(): string
    {
        return 'is_enabled';
    }

    public function getRedeemerTypeColumn(): string
    {
        return 'redeemer_type';
    }

    public function getRedeemerIdColumn(): string
    {
        return 'redeemer_id';
    }

    public function getDataColumn(): string
    {
        return 'data';
    }

    public function getCreatedAtColumn(): string
    {
        return 'created_at';
    }

    public function getUpdatedAtColumn(): string
    {
        return 'updated_at';
    }
}
