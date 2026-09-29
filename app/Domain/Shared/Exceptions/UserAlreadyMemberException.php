<?php

namespace App\Domain\Shared\Exceptions;

class UserAlreadyMemberException extends DomainException
{
    public static function forEmail(string $email): self
    {
        return new self(sprintf('User %s is already a member of this business.', $email));
    }
}
