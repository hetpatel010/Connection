<?php

namespace App\Domain\Business;

final class BusinessBranding
{
    public const LOGO_PATH = 'logo_path';

    public const TERMS_AND_CONDITIONS = 'terms_and_conditions';

    public const INVOICE_FOOTER = 'invoice_footer';

    public const BANK_NAME = 'bank_name';

    public const BANK_ACCOUNT = 'bank_account';

    public const BANK_IFSC = 'bank_ifsc';

    public const UPI_ID = 'upi_id';

    public const SIGNATURE_LABEL = 'signature_label';

    public const WEBSITE = 'website';

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return [
            self::LOGO_PATH,
            self::TERMS_AND_CONDITIONS,
            self::INVOICE_FOOTER,
            self::BANK_NAME,
            self::BANK_ACCOUNT,
            self::BANK_IFSC,
            self::UPI_ID,
            self::SIGNATURE_LABEL,
            self::WEBSITE,
        ];
    }
}
