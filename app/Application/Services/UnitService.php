<?php

namespace App\Application\Services;

use App\Domain\Products\DefaultUnits;
use App\Models\Business;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UnitService
{
    public function seedDefaultForBusiness(Business $business): Collection
    {
        if (Unit::query()->withoutGlobalScopes()->where('business_id', $business->id)->exists()) {
            return Unit::query()->withoutGlobalScopes()->where('business_id', $business->id)->get();
        }

        return DB::transaction(function () use ($business): Collection {
            $units = collect();

            foreach (DefaultUnits::definitions() as $definition) {
                if ($definition['is_default']) {
                    Unit::query()
                        ->where('business_id', $business->id)
                        ->update(['is_default' => false]);
                }

                $units->push(Unit::query()->create([
                    'business_id' => $business->id,
                    'name' => $definition['name'],
                    'symbol' => $definition['symbol'],
                    'decimal_places' => $definition['decimal_places'],
                    'is_default' => $definition['is_default'],
                    'is_system' => true,
                ]));
            }

            return $units;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(Business $business, array $attributes): Unit
    {
        if ($attributes['is_default'] ?? false) {
            Unit::query()
                ->where('business_id', $business->id)
                ->update(['is_default' => false]);
        }

        return Unit::query()->create([
            'business_id' => $business->id,
            'name' => $attributes['name'],
            'symbol' => $attributes['symbol'],
            'decimal_places' => $attributes['decimal_places'] ?? 0,
            'is_default' => $attributes['is_default'] ?? false,
            'is_system' => false,
        ]);
    }
}
