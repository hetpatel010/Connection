<?php

namespace App\Domain\Shared\Exceptions;

class RoleInUseException extends DomainException
{
    public static function forRole(string $roleName): self
    {
        return new self("The role \"{$roleName}\" is assigned to team members and cannot be deleted.");
    }
}
