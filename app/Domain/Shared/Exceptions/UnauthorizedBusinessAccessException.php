<?php

namespace App\Domain\Shared\Exceptions;

class UnauthorizedBusinessAccessException extends DomainException
{
    public function __construct(string $message = 'You do not have access to this business.')
    {
        parent::__construct($message);
    }
}
