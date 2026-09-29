<?php

namespace App\Http\Controllers;

use App\Application\Services\MailConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportException;
use Throwable;

class VendorContactController extends Controller
{
    public function __construct(
        private MailConfigurationService $mailConfiguration,
    ) {}

    public function create(): View
    {
        $mailBlockReason = $this->mailConfiguration->blockingReason();

        return view('settings.invoice-template-contact', [
            'vendorEmail' => $this->mailConfiguration->recipient(),
            'defaultSubject' => $this->defaultSubject(),
            'mailIsDeliverable' => $mailBlockReason === null,
            'mailBlockReason' => $mailBlockReason,
        ]);
    }

    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $mailBlockReason = $this->mailConfiguration->blockingReason();

        if ($mailBlockReason !== null) {
            Log::warning('Outbound mail blocked by configuration.', $this->mailConfiguration->logContext());

            return back()
                ->withInput()
                ->with('error', $mailBlockReason);
        }

        $sender = $request->user();
        $business = business_context()->business();

        $body = implode("\n\n", array_filter([
            $validated['message'],
            '—',
            $sender?->name !== null && $sender->name !== '' ? __('Sent by: :name', ['name' => $sender->name]) : null,
            $sender?->email !== null ? __('Reply to: :email', ['email' => $sender->email]) : null,
            $business !== null ? __('Business: :name', ['name' => $business->name]) : null,
        ]));

        $recipient = $this->mailConfiguration->recipient();

        try {
            Mail::raw($body, function ($message) use ($validated, $sender, $recipient): void {
                $message
                    ->to($recipient)
                    ->subject($validated['subject']);

                if ($sender !== null && $sender->email !== null) {
                    $message->replyTo($sender->email, $sender->name ?? '');
                }
            });
        } catch (TransportException $exception) {
            // Configuration and connectivity problems are expected operator issues rather
            // than application defects, so they are recorded as a scrubbed warning that
            // names the transport instead of a stack trace carrying transport internals.
            Log::error('Mail transport failure.', $this->mailConfiguration->logContext() + [
                'recipient' => $recipient,
                'exception' => $exception::class,
                'detail' => Str::limit($exception->getMessage(), 300),
            ]);

            return back()
                ->withInput()
                ->with('error', __('Your message could not be sent. Please check the mail configuration or contact us directly.'));
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', __('Your message could not be sent. Please check the mail configuration or contact us directly.'));
        }

        return redirect()
            ->route('settings.invoice-templates.contact')
            ->with('status', __('Your message has been sent to SVEO. We will get back to you shortly.'));
    }

    private function defaultSubject(): string
    {
        return __('Inquiry about Invoice Template');
    }
}
