<?php

namespace App\Domain\Shared\Exceptions;

class InsufficientStockException extends DomainException
{
    public static function forProduct(string $productName, float $requested, float $available): self
    {
        return new self(sprintf(
            'Insufficient stock for %s. Requested: %s, Available: %s.',
            $productName,
            rtrim(rtrim(number_format($requested, 4, '.', ''), '0'), '.'),
            rtrim(rtrim(number_format($available, 4, '.', ''), '0'), '.'),
        ));
    }
}
