<?php

namespace App\Domain\Products;

use App\Domain\Shared\Exceptions\InvalidGstRateException;

enum GstRate
{
    case Nil;

    case Five;

    case Twelve;

    case Eighteen;

    case TwentyEight;

    /**
     * The highest rate that may be stored against a product or transaction line.
     */
    public const MAXIMUM = 100.0;

    public function rate(): float
    {
        return match ($this) {
            self::Nil => 0.0,
            self::Five => 5.0,
            self::Twelve => 12.0,
            self::Eighteen => 18.0,
            self::TwentyEight => 28.0,
        };
    }

    public function label(): string
    {
        return $this->rate().'%';
    }

    public function isNil(): bool
    {
        return $this === self::Nil;
    }

    /**
     * The common GST slabs offered throughout the application, in ascending order.
     *
     * @return list<float>
     */
    public static function slabs(): array
    {
        return array_map(
            static fn (self $rate): float => $rate->rate(),
            self::cases(),
        );
    }

    /**
     * Resolve a stored or submitted rate back to its slab, when it matches one.
     */
    public static function fromRate(mixed $rate): ?self
    {
        if (! self::isValid($rate)) {
            return null;
        }

        foreach (self::cases() as $case) {
            if ($case->rate() === (float) $rate) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Determine whether a value is a GST rate the domain is willing to store.
     *
     * An empty value is treated as nil rather than invalid, so that optional
     * product and line attributes fall back to the configured 0% behaviour.
     */
    public static function isValid(mixed $rate): bool
    {
        if ($rate === null || $rate === '') {
            return true;
        }

        if (! is_numeric($rate)) {
            return false;
        }

        $value = (float) $rate;

        return $value >= 0
            && $value <= self::MAXIMUM
            && round($value, 2) === $value;
    }

    /**
     * Coerce a rate coming from user input or persisted data into a storable float.
     *
     * @throws InvalidGstRateException
     */
    public static function normalize(mixed $rate): float
    {
        if ($rate === null || $rate === '') {
            return self::Nil->rate();
        }

        if (! self::isValid($rate)) {
            throw InvalidGstRateException::forValue(is_scalar($rate) ? (string) $rate : get_debug_type($rate));
        }

        return (float) $rate;
    }
}
