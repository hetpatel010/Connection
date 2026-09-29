<?php

namespace App\Application\Services;

use App\Models\Business;
use App\Models\DocumentSequence;
use App\Models\FinancialYear;
use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    public function nextNumber(
        Business $business,
        string $documentType,
        string $prefix,
        ?FinancialYear $financialYear = null,
    ): string {
        return DB::transaction(function () use ($business, $documentType, $prefix, $financialYear): string {
            $sequence = DocumentSequence::query()
                ->withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->where('document_type', $documentType)
                ->when(
                    $financialYear,
                    fn ($query) => $query->where('financial_year_id', $financialYear->id),
                    fn ($query) => $query->whereNull('financial_year_id'),
                )
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                $sequence = DocumentSequence::query()->create([
                    'business_id' => $business->id,
                    'document_type' => $documentType,
                    'prefix' => $prefix,
                    'financial_year_id' => $financialYear?->id,
                    'next_number' => 1,
                ]);
            }

            $number = $sequence->next_number;
            $sequence->increment('next_number');

            return sprintf('%s-%06d', $prefix, $number);
        });
    }
}
