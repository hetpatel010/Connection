<?php

namespace App\Domain\Accounting\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

class InvalidJournalLineException extends DomainException
{
    public function __construct(string $message = 'Each journal line must have either a debit or a credit amount.')
    {
        parent::__construct($message);
    }
}
