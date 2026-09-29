<?php

namespace App\Domain\Shared\Exceptions;

use App\Support\Money;

class InvalidPaymentAllocationException extends DomainException
{
    public static function exceedsPaymentAmount(int $allocated, int $paymentAmount): self
    {
        return new self(sprintf(
            'Total allocated amount (%s) exceeds payment amount (%s).',
            Money::format($allocated),
            Money::format($paymentAmount),
        ));
    }

    public static function exceedsOutstanding(string $documentNumber, int $allocated, int $outstanding): self
    {
        return new self(sprintf(
            'Allocation for %s (%s) exceeds outstanding balance (%s).',
            $documentNumber,
            Money::format($allocated),
            Money::format($outstanding),
        ));
    }

    public static function partyMismatch(string $documentNumber): self
    {
        return new self(sprintf('Document %s does not belong to the selected party.', $documentNumber));
    }

    public static function invalidDocumentType(string $documentNumber): self
    {
        return new self(sprintf('Document %s cannot be allocated to this payment type.', $documentNumber));
    }

    public static function partyRequired(): self
    {
        return new self('A party must be selected when allocating to documents.');
    }
}
