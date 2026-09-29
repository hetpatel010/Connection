<?php

namespace App\Domain\Shared\Exceptions;

class FinancialYearClosedException extends DomainException
{
    public function __construct(string $message = 'This financial year is closed and cannot be modified.')
    {
        parent::__construct($message);
    }
}
