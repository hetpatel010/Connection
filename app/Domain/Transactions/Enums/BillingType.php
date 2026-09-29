<?php

namespace App\Domain\Transactions\Enums;

enum BillingType: string
{
    case Gst = 'gst';

    case NonGst = 'non_gst';

    public function isGst(): bool
    {
        return $this === self::Gst;
    }

    public function label(): string
    {
        return match ($this) {
            self::Gst => __('GST Tax Invoice'),
            self::NonGst => __('Non-GST Bill'),
        };
    }

    public function documentTitle(): string
    {
        return match ($this) {
            self::Gst => __('Tax Invoice'),
            self::NonGst => __('Bill / Invoice'),
        };
    }
}
