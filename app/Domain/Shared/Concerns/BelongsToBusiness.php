<?php

namespace App\Domain\Shared\Concerns;

use App\Application\Services\BusinessContext;
use App\Domain\Shared\Scopes\BusinessScope;
use App\Models\Business;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToBusiness
{
    public static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope(new BusinessScope);

        static::creating(function ($model): void {
            if (empty($model->business_id) && app(BusinessContext::class)->hasBusiness()) {
                $model->business_id = app(BusinessContext::class)->businessId();
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
