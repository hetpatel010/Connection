<?php

namespace App\Domain\Shared\Exceptions;

class CannotModifyOwnerMembershipException extends DomainException
{
    public static function cannotChangeRole(): self
    {
        return new self('The business owner role cannot be changed.');
    }

    public static function cannotRemove(): self
    {
        return new self('The business owner cannot be removed.');
    }

    public static function cannotAssignOwner(): self
    {
        return new self('The owner role cannot be assigned through user management.');
    }
}
