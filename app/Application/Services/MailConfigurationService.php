<?php

namespace App\Application\Services;

/**
 * Diagnostics for the application's outbound mail configuration.
 *
 * Every message in the application is sent through the default mailer, so a single
 * broken setting makes all of them fail. The service answers one question: can the
 * current configuration actually deliver a message, and if not, why — without ever
 * reading or exposing a credential.
 */
class MailConfigurationService
{
    /**
     * Transports that write the message to a local log instead of delivering it,
     * which would otherwise let the application report a success that never happened.
     *
     * @var list<string>
     */
    private const NON_DELIVERING_MAILERS = ['log'];

    /**
     * Literal values shipped in the .env templates that were never replaced.
     *
     * @var list<string>
     */
    private const PLACEHOLDER_VALUES = [
        'change-me',
        'changeme',
        'example',
        'mail.example.com',
        'placeholder',
        'smtp.example.com',
        'smtp.example.org',
        'your-username',
        'your_password',
        'your-password',
    ];

    /**
     * Used when BRANDING_VENDOR_EMAIL is absent so an unset key still reaches a
     * monitored inbox instead of failing.
     */
    private const FALLBACK_RECIPIENT = 'hello@sveo.in';

    /**
     * Shared guidance appended to every configuration problem so the person reading
     * the message always knows the next step and still has a way to reach out.
     */
    private const REMEDY = 'You can still use the "Request custom template" button to reach us from your own email application.';

    /**
     * The recipient address for the vendor contact channel, or an empty string when
     * it is not usable.
     */
    public function recipient(): string
    {
        $recipient = trim((string) branding('vendor.email', self::FALLBACK_RECIPIENT));

        if ($recipient === '') {
            $recipient = self::FALLBACK_RECIPIENT;
        }

        return $this->isValidAddress($recipient) ? $recipient : '';
    }

    public function recipientIsValid(): bool
    {
        return $this->recipient() !== '';
    }

    public function isDeliverable(): bool
    {
        return $this->blockingReason() === null;
    }

    /**
     * The specific reason the current configuration cannot deliver mail, or null
     * when it can. The message is safe to render in the UI: it names the offending
     * environment key and never its value when the value is a credential.
     */
    public function blockingReason(): ?string
    {
        return $this->recipientIsValid()
            ? $this->transportReason()
            : $this->remedy(__(
                'The vendor email address in your .env file (BRANDING_VENDOR_EMAIL) is missing or is not a valid address, so there is nowhere to send the message.'
            ));
    }

    /**
     * Log-safe description of the active transport. Credentials are deliberately
     * excluded: only the host, port and transport name are recorded.
     *
     * @return array<string, mixed>
     */
    public function logContext(): array
    {
        $mailer = $this->mailer();

        return [
            'mailer' => $mailer,
            'transport' => (string) config("mail.mailers.{$mailer}.transport"),
            'host' => (string) config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'queue_connection' => (string) config('queue.default'),
            'config_cached' => app()->configurationIsCached(),
        ];
    }

    private function transportReason(): ?string
    {
        $mailer = $this->mailer();

        if (in_array($mailer, self::NON_DELIVERING_MAILERS, true)) {
            return $this->remedy(__(
                'Email delivery is not configured for this installation because MAIL_MAILER is set to ":mailer", which only records messages locally instead of delivering them. Set MAIL_MAILER to a real transport such as smtp in your .env file, then try again.',
                ['mailer' => $mailer]
            ));
        }

        if ($mailer !== 'smtp') {
            return null;
        }

        foreach ([
            'MAIL_HOST' => config('mail.mailers.smtp.host'),
            'MAIL_USERNAME' => config('mail.mailers.smtp.username'),
            'MAIL_PASSWORD' => config('mail.mailers.smtp.password'),
        ] as $key => $value) {
            if (! is_string($value) || trim($value) === '') {
                return $this->remedy(__(
                    'Email delivery is not configured for this installation because :key is missing from your .env file.',
                    ['key' => $key]
                ));
            }
        }

        if ($this->isPlaceholder((string) config('mail.mailers.smtp.host'))) {
            return $this->remedy(__(
                'Email delivery is not configured for this installation because MAIL_HOST in your .env file still holds the placeholder ":host". Replace it with the SMTP host from your mail provider, then try again.',
                ['host' => (string) config('mail.mailers.smtp.host')]
            ));
        }

        $port = config('mail.mailers.smtp.port');
        if (! is_numeric($port) || (int) $port < 1 || (int) $port > 65535) {
            return $this->remedy(__(
                'Email delivery is not configured for this installation because MAIL_PORT in your .env file is not a valid port number.'
            ));
        }

        $from = (string) config('mail.from.address');
        if (! $this->isValidAddress($from)) {
            return $this->remedy(__(
                'Email delivery is not configured for this installation because MAIL_FROM_ADDRESS in your .env file is not a valid address.'
            ));
        }

        return null;
    }

    private function remedy(string $problem): string
    {
        return trim($problem.' '.self::REMEDY);
    }

    private function mailer(): string
    {
        return (string) config('mail.default');
    }

    private function isValidAddress(string $address): bool
    {
        return $address !== '' && filter_var($address, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Placeholders are never valid hosts but the value is safe to echo back to the
     * person who owns the .env file, so the message can name it explicitly.
     */
    private function isPlaceholder(string $value): bool
    {
        $value = strtoupper(trim($value));

        if ($value === '') {
            return true;
        }

        if (str_contains($value, 'YOUR_') || str_contains($value, 'YOUR-') || str_contains($value, '<')) {
            return true;
        }

        return in_array(strtolower(trim($value)), self::PLACEHOLDER_VALUES, true);
    }
}
