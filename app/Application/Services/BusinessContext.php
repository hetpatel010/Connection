<?php

namespace App\Application\Services;

use App\Domain\Shared\Exceptions\UnauthorizedBusinessAccessException;
use App\Models\Business;
use App\Models\FinancialYear;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class BusinessContext
{
    public const BUSINESS_SESSION_KEY = 'current_business_id';

    public const FINANCIAL_YEAR_SESSION_KEY = 'current_financial_year_id';

    private ?Business $business = null;

    private ?FinancialYear $financialYear = null;

    public function business(): ?Business
    {
        return $this->business;
    }

    public function businessId(): ?string
    {
        return $this->business?->id;
    }

    public function financialYear(): ?FinancialYear
    {
        return $this->financialYear;
    }

    public function financialYearId(): ?string
    {
        return $this->financialYear?->id;
    }

    public function hasBusiness(): bool
    {
        return $this->business !== null;
    }

    public function resolveForUser(User $user): void
    {
        $businessId = session(self::BUSINESS_SESSION_KEY);
        $financialYearId = session(self::FINANCIAL_YEAR_SESSION_KEY);

        $this->business = null;
        $this->financialYear = null;

        if ($businessId !== null) {
            $business = $user->businesses()->where('businesses.id', $businessId)->first();

            if ($business !== null) {
                $this->business = $business;
            } else {
                session()->forget([self::BUSINESS_SESSION_KEY, self::FINANCIAL_YEAR_SESSION_KEY]);
            }
        }

        if ($this->business === null) {
            $firstBusiness = $user->businesses()->orderBy('name')->first();

            if ($firstBusiness !== null) {
                $this->setBusiness($firstBusiness, $user);
                $financialYearId = session(self::FINANCIAL_YEAR_SESSION_KEY);
            }
        }

        if ($this->business !== null && $financialYearId !== null) {
            $financialYear = FinancialYear::query()
                ->withoutGlobalScopes()
                ->where('business_id', $this->business->id)
                ->where('id', $financialYearId)
                ->first();

            if ($financialYear !== null) {
                $this->financialYear = $financialYear;
            } else {
                session()->forget(self::FINANCIAL_YEAR_SESSION_KEY);
            }
        }

        if ($this->business !== null && $this->financialYear === null) {
            $currentFinancialYear = FinancialYear::query()
                ->withoutGlobalScopes()
                ->where('business_id', $this->business->id)
                ->where('is_current', true)
                ->first();

            if ($currentFinancialYear !== null) {
                $this->setFinancialYear($currentFinancialYear, $user);
            }
        }
    }

    public function setBusiness(Business $business, User $user): void
    {
        if (! $user->belongsToBusiness($business)) {
            throw new UnauthorizedBusinessAccessException;
        }

        $this->business = $business;
        session([self::BUSINESS_SESSION_KEY => $business->id]);

        $currentFinancialYear = FinancialYear::query()
            ->withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('is_current', true)
            ->first();

        if ($currentFinancialYear !== null) {
            $this->setFinancialYear($currentFinancialYear, $user);
        } else {
            $this->financialYear = null;
            session()->forget(self::FINANCIAL_YEAR_SESSION_KEY);
        }
    }

    public function setFinancialYear(FinancialYear $financialYear, User $user): void
    {
        if ($this->business === null || $financialYear->business_id !== $this->business->id) {
            throw new UnauthorizedBusinessAccessException('Financial year does not belong to the current business.');
        }

        if (! $user->belongsToBusiness($this->business)) {
            throw new UnauthorizedBusinessAccessException;
        }

        $this->financialYear = $financialYear;
        session([self::FINANCIAL_YEAR_SESSION_KEY => $financialYear->id]);
    }

    public function userCan(string $permission): bool
    {
        $user = Auth::user();

        if ($user === null || $this->business === null) {
            return false;
        }

        return $user->hasPermissionInBusiness($permission, $this->business);
    }

    public function clear(): void
    {
        $this->business = null;
        $this->financialYear = null;
        session()->forget([self::BUSINESS_SESSION_KEY, self::FINANCIAL_YEAR_SESSION_KEY]);
    }
}
