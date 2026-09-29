<?php

use App\Application\Services\BusinessContext;
use App\Application\Services\MailConfigurationService;
use App\Domain\Authorization\SystemRoles;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;

function configureSmtpForDelivery(array $overrides = []): void
{
    config([
        'mail.default' => 'array',
        'mail.mailers.smtp.host' => 'smtp.postmarkapp.com',
        'mail.mailers.smtp.port' => 587,
        'mail.mailers.smtp.username' => 'billing@example.org',
        'mail.mailers.smtp.password' => 'super-secret-app-password',
        'mail.from.address' => 'billing@example.org',
        'branding.vendor.email' => 'hello@sveo.in',
    ] + $overrides);
}

function sentMessages(): array
{
    return app('mail.manager')->mailer('array')->getSymfonyTransport()->messages();
}

test('email sveo button links to the compose page instead of a bare mailto', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $response = $this->actingAs($user)
        ->withSession([BusinessContext::BUSINESS_SESSION_KEY => $business->id])
        ->get(route('settings.invoice-templates'));

    $response->assertOk()
        ->assertSee('href="'.route('settings.invoice-templates.contact').'"', false)
        ->assertSee(__('Email SVEO'), false)
        ->assertSee(__('Request custom template'), false);
});

test('compose page prefills the vendor address and subject', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $this->actingAs($user)
        ->withSession([BusinessContext::BUSINESS_SESSION_KEY => $business->id])
        ->get(route('settings.invoice-templates.contact'))
        ->assertOk()
        ->assertSee('value="'.branding('vendor.email').'"', false)
        ->assertSee('value="'.e(__('Inquiry about Invoice Template')).'"', false)
        ->assertSee('name="subject"', false)
        ->assertSee('name="message"', false)
        ->assertSee(__('Send Email'), false)
        ->assertSee(route('settings.invoice-templates'), false);
});

test('compose page is hidden from users without manage settings permission', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness();

    $salesperson = User::factory()->create();
    $this->attachUserToBusiness($salesperson, $business, SystemRoles::SALESPERSON);

    $this->actingAs($salesperson)
        ->withSession([BusinessContext::BUSINESS_SESSION_KEY => $business->id])
        ->get(route('settings.invoice-templates.contact'))
        ->assertForbidden();

    $this->actingAs($owner)
        ->withSession([BusinessContext::BUSINESS_SESSION_KEY => $business->id])
        ->get(route('settings.invoice-templates'))
        ->assertOk();
});

test('submitting the compose form delivers the message to the vendor address', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    config(['mail.default' => 'array']);

    $this->actingAs($user)
        ->withSession([BusinessContext::BUSINESS_SESSION_KEY => $business->id])
        ->post(route('settings.invoice-templates.contact.store'), [
            'subject' => 'Inquiry about Invoice Template',
            'message' => 'We need a thermal printer layout for B2B invoices.',
        ])
        ->assertRedirect(route('settings.invoice-templates.contact'))
        ->assertSessionHas('status');

    $messages = sentMessages();

    expect($messages)->toHaveCount(1);

    $original = $messages[0]->getOriginalMessage();

    expect($original->getTo())->toHaveCount(1)
        ->and($original->getTo()[0]->getAddress())->toBe(branding('vendor.email'))
        ->and($original->getSubject())->toBe('Inquiry about Invoice Template')
        ->and($original->getReplyTo())->toHaveCount(1)
        ->and($original->getReplyTo()[0]->getAddress())->toBe($user->email);

    expect($original->getTextBody())
        ->toContain('We need a thermal printer layout for B2B invoices.')
        ->toContain($business->name);
});

test('submitting the compose form requires a subject and a message', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    config(['mail.default' => 'array']);

    $this->actingAs($user)
        ->withSession([BusinessContext::BUSINESS_SESSION_KEY => $business->id])
        ->from(route('settings.invoice-templates.contact'))
        ->post(route('settings.invoice-templates.contact.store'), [
            'subject' => '',
            'message' => '',
        ])
        ->assertRedirect(route('settings.invoice-templates.contact'))
        ->assertSessionHasErrors(['subject', 'message']);

    expect(sentMessages())->toBeEmpty();
});

test('compose form reports a configuration problem instead of a false success', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    config(['mail.default' => 'log']);

    $this->actingAs($user)
        ->withSession([BusinessContext::BUSINESS_SESSION_KEY => $business->id])
        ->post(route('settings.invoice-templates.contact.store'), [
            'subject' => 'Inquiry about Invoice Template',
            'message' => 'We need a thermal printer layout for B2B invoices.',
        ])
        ->assertRedirect()
        ->assertSessionHas('error')
        ->assertSessionMissing('status');
});

test('compose form names the unresolved smtp host placeholder as the reason', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    configureSmtpForDelivery([
        'mail.default' => 'smtp',
        'mail.mailers.smtp.host' => 'YOUR_SMTP_HOST',
    ]);

    $this->actingAs($user)
        ->withSession([BusinessContext::BUSINESS_SESSION_KEY => $business->id])
        ->get(route('settings.invoice-templates.contact'))
        ->assertOk()
        ->assertSee('MAIL_HOST')
        ->assertSee('YOUR_SMTP_HOST', false);

    $this->actingAs($user)
        ->withSession([BusinessContext::BUSINESS_SESSION_KEY => $business->id])
        ->from(route('settings.invoice-templates.contact'))
        ->post(route('settings.invoice-templates.contact.store'), [
            'subject' => 'Inquiry about Invoice Template',
            'message' => 'We need a thermal printer layout for B2B invoices.',
        ])
        ->assertRedirect(route('settings.invoice-templates.contact'))
        ->assertSessionHas('error')
        ->assertSessionMissing('status');

    expect(sentMessages())->toBeEmpty();
});

test('compose form names a missing smtp credential without echoing its value', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    configureSmtpForDelivery([
        'mail.default' => 'smtp',
        'mail.mailers.smtp.password' => '',
    ]);

    $this->actingAs($user)
        ->withSession([BusinessContext::BUSINESS_SESSION_KEY => $business->id])
        ->from(route('settings.invoice-templates.contact'))
        ->post(route('settings.invoice-templates.contact.store'), [
            'subject' => 'Inquiry about Invoice Template',
            'message' => 'We need a thermal printer layout for B2B invoices.',
        ])
        ->assertSessionHas('error')
        ->assertSessionMissing('status');

    expect(session('error'))->toContain('MAIL_PASSWORD')
        ->and(session('error'))->not->toContain('super-secret-app-password');
});

test('compose form rejects an invalid vendor recipient instead of throwing', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    configureSmtpForDelivery(['branding.vendor.email' => 'not-an-address']);

    $this->actingAs($user)
        ->withSession([BusinessContext::BUSINESS_SESSION_KEY => $business->id])
        ->from(route('settings.invoice-templates.contact'))
        ->post(route('settings.invoice-templates.contact.store'), [
            'subject' => 'Inquiry about Invoice Template',
            'message' => 'We need a thermal printer layout for B2B invoices.',
        ])
        ->assertSessionHas('error')
        ->assertSessionMissing('status');

    expect(session('error'))->toContain('BRANDING_VENDOR_EMAIL')
        ->and(sentMessages())->toBeEmpty();
});

test('compose form reports a transport failure and logs it without credentials', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    configureSmtpForDelivery();

    Mail::shouldReceive('raw')
        ->once()
        ->andThrow(new TransportException('Connection could not be established with host "smtp.postmarkapp.com:587"'));

    Log::spy();

    $this->actingAs($user)
        ->withSession([BusinessContext::BUSINESS_SESSION_KEY => $business->id])
        ->from(route('settings.invoice-templates.contact'))
        ->post(route('settings.invoice-templates.contact.store'), [
            'subject' => 'Inquiry about Invoice Template',
            'message' => 'We need a thermal printer layout for B2B invoices.',
        ])
        ->assertSessionHas('error')
        ->assertSessionMissing('status');

    Log::shouldHaveReceived('error')
        ->once()
        ->withArgs(function (string $message, array $context): bool {
            return $message === 'Mail transport failure.'
                && $context['host'] === 'smtp.postmarkapp.com'
                && $context['port'] === 587
                && $context['recipient'] === 'hello@sveo.in'
                && $context['exception'] === TransportException::class
                && ! in_array('super-secret-app-password', $context, true)
                && ! str_contains(json_encode($context), 'super-secret-app-password');
        });
});

test('mail configuration diagnostics never expose the smtp password', function () {
    configureSmtpForDelivery(['mail.default' => 'smtp']);

    $context = app(MailConfigurationService::class)->logContext();

    expect($context)->toHaveKeys(['mailer', 'transport', 'host', 'port', 'queue_connection', 'config_cached'])
        ->and(json_encode($context))->not->toContain('super-secret-app-password')
        ->and($context['host'])->toBe('smtp.postmarkapp.com')
        ->and($context['port'])->toBe(587);
});

test('the smtp transport scheme is derived from the encryption setting', function (string $encryption, ?string $expected) {
    $originalEnv = $_ENV;
    $originalServer = $_SERVER;

    $_ENV['MAIL_SCHEME'] = 'null';
    $_ENV['MAIL_ENCRYPTION'] = $encryption;

    try {
        $scheme = (require config_path('mail.php'))['mailers']['smtp']['scheme'];
    } finally {
        $_ENV = $originalEnv;
        $_SERVER = $originalServer;
    }

    expect($scheme)->toBe($expected);
})->with([
    ['null', null],
    ['', null],
    ['tls', 'smtp'],
    ['starttls', 'smtp'],
    ['ssl', 'smtps'],
    ['smtps', 'smtps'],
    ['none', null],
]);

test('an explicit MAIL_SCHEME always wins over MAIL_ENCRYPTION', function () {
    $originalEnv = $_ENV;
    $originalServer = $_SERVER;

    $_ENV['MAIL_SCHEME'] = 'smtps';
    $_ENV['MAIL_ENCRYPTION'] = 'tls';

    try {
        $scheme = (require config_path('mail.php'))['mailers']['smtp']['scheme'];
    } finally {
        $_ENV = $originalEnv;
        $_SERVER = $originalServer;
    }

    expect($scheme)->toBe('smtps');
});
