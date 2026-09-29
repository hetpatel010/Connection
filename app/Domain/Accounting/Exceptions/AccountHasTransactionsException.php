<?php

namespace App\Domain\Accounting\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

class AccountHasTransactionsException extends DomainException
{
    public static function forAccount(string $accountCode, string $accountName): self
    {
        return new self("The account \"{$accountCode} {$accountName}\" is already used in accounting records and cannot be deleted. Deactivate it instead to stop it being used in new transactions.");
    }

    public static function forAccountWithChildren(string $accountCode, string $accountName): self
    {
        return new self("The account \"{$accountCode} {$accountName}\" has sub accounts and cannot be deleted. Move or delete its sub accounts first.");
    }

    public static function forLockedAttribute(string $accountCode, string $attribute): self
    {
        return new self("The \"{$attribute}\" of account \"{$accountCode}\" cannot be changed because it would corrupt existing accounting records.");
    }
}
