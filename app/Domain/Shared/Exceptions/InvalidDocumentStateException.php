<?php

namespace App\Domain\Shared\Exceptions;

class InvalidDocumentStateException extends DomainException
{
    public static function mustBeDraft(string $action): self
    {
        return new self(sprintf('Only draft documents can be %s.', $action));
    }

    public static function mustBeConfirmed(string $action): self
    {
        return new self(sprintf('Only confirmed documents can be %s.', $action));
    }
}
