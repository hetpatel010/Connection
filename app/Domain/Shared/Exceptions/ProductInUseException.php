<?php

namespace App\Domain\Shared\Exceptions;

class ProductInUseException extends DomainException
{
    public static function forProduct(string $productName): self
    {
        return new self("The product \"{$productName}\" is used in invoices, purchases or stock movements and cannot be deleted.");
    }
}
