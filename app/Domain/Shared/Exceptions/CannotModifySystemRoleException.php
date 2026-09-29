<?php

namespace App\Domain\Shared\Exceptions;

class CannotModifySystemRoleException extends DomainException
{
    public static function readOnly(): self
    {
        return new self('System roles cannot be modified.');
    }
}
