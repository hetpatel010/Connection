<?php

namespace App\Domain\Shared\Exceptions;

class PartyHasTransactionsException extends DomainException
{
    public static function forParty(string $partyName): self
    {
        return new self("The party \"{$partyName}\" has existing transactions and cannot be deleted.");
    }
}
