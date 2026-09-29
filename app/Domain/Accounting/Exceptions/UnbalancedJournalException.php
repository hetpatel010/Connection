<?php

namespace App\Domain\Accounting\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

class UnbalancedJournalException extends DomainException
{
    public function __construct(int $totalDebits, int $totalCredits)
    {
        parent::__construct(
            sprintf('Journal is unbalanced. Debits: %d, Credits: %d', $totalDebits, $totalCredits)
        );
    }
}
