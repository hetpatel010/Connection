<?php

namespace App\Application\Services;

use App\Domain\FinancialYear\Enums\FinancialYearStatus;
use App\Domain\Shared\Exceptions\FinancialYearClosedException;
use App\Models\Business;
use App\Models\FinancialYear;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialYearService
{
    public function createCurrentIndianFinancialYear(Business $business): FinancialYear
    {
        $today = Carbon::today(config('app.timezone', 'Asia/Kolkata'));

        if ($today->month >= 4) {
            $startsOn = Carbon::create($today->year, 4, 1);
            $endsOn = Carbon::create($today->year + 1, 3, 31);
            $label = sprintf('%d-%02d', $today->year, ($today->year + 1) % 100);
        } else {
            $startsOn = Carbon::create($today->year - 1, 4, 1);
            $endsOn = Carbon::create($today->year, 3, 31);
            $label = sprintf('%d-%02d', $today->year - 1, $today->year % 100);
        }

        return $this->create($business, [
            'label' => $label,
            'starts_on' => $startsOn->toDateString(),
            'ends_on' => $endsOn->toDateString(),
            'is_current' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(Business $business, array $attributes): FinancialYear
    {
        return DB::transaction(function () use ($business, $attributes): FinancialYear {
            if ($attributes['is_current'] ?? false) {
                FinancialYear::query()
                    ->withoutGlobalScopes()
                    ->where('business_id', $business->id)
                    ->update(['is_current' => false]);
            }

            return FinancialYear::query()->create([
                'business_id' => $business->id,
                'label' => $attributes['label'],
                'starts_on' => $attributes['starts_on'],
                'ends_on' => $attributes['ends_on'],
                'status' => FinancialYearStatus::Open,
                'is_current' => $attributes['is_current'] ?? false,
            ]);
        });
    }

    public function assertWritable(FinancialYear $financialYear): void
    {
        if ($financialYear->isLocked()) {
            throw new FinancialYearClosedException;
        }
    }

    public function close(FinancialYear $financialYear, User $user): FinancialYear
    {
        return DB::transaction(function () use ($financialYear, $user): FinancialYear {
            if ($financialYear->status === FinancialYearStatus::Closed) {
                throw new FinancialYearClosedException('Financial year is already closed.');
            }

            $financialYear->update([
                'status' => FinancialYearStatus::Closed,
                'closed_at' => now(),
                'closed_by' => $user->id,
            ]);

            return $financialYear->fresh(['closedByUser']);
        });
    }

    public function openNext(Business $business, User $user): FinancialYear
    {
        return DB::transaction(function () use ($business, $user): FinancialYear {
            $latest = FinancialYear::query()
                ->withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->orderByDesc('ends_on')
                ->firstOrFail();

            if ($latest->status !== FinancialYearStatus::Closed) {
                throw new FinancialYearClosedException('Close the latest financial year before opening the next one.');
            }

            $startsOn = $latest->ends_on->copy()->addDay();
            $endsOn = $startsOn->copy()->addYear()->subDay();
            $label = sprintf('%d-%02d', $startsOn->year, ($startsOn->year + 1) % 100);

            $exists = FinancialYear::query()
                ->withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->where('label', $label)
                ->exists();

            if ($exists) {
                throw new FinancialYearClosedException("Financial year {$label} already exists.");
            }

            $newYear = $this->create($business, [
                'label' => $label,
                'starts_on' => $startsOn->toDateString(),
                'ends_on' => $endsOn->toDateString(),
                'is_current' => true,
            ]);

            app(OpeningBalanceCarryForwardService::class)->carryForward(
                $business,
                $latest,
                $newYear,
                $user,
            );

            return $newYear;
        });
    }
}
