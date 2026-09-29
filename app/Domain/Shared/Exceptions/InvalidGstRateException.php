<?php

namespace App\Domain\Shared\Exceptions;

class InvalidGstRateException extends DomainException
{
    public static function forValue(string $value): self
    {
        return new self("The GST rate \"{$value}\" is not a valid rate. Use a number between 0 and 100 with at most two decimal places.");
    }
}
