<?php

namespace App\Providers;

use App\Application\Services\BusinessContext;
use App\Domain\Authorization\Permissions;
use App\Models\Account;
use App\Models\Business;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\Expense;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseBill;
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Policies\AccountPolicy;
use App\Policies\BusinessPolicy;
use App\Policies\CreditNotePolicy;
use App\Policies\DebitNotePolicy;
use App\Policies\ExpensePolicy;
use App\Policies\FinancialYearPolicy;
use App\Policies\PartyPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PurchaseBillPolicy;
use App\Policies\RolePolicy;
use App\Policies\SalesInvoicePolicy;
use App\Support\RouteTitles;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BusinessContext::class);
    }

    public function boot(): void
    {
        if (config('nativephp-internal.running')) {
            URL::forceRootUrl(request()->getSchemeAndHttpHost());

            config([
                'session.driver' => 'file',
                'cache.default' => 'file',
                'queue.default' => 'sync',
            ]);
        }

        Relation::morphMap([
            'sales_invoice' => SalesInvoice::class,
            'purchase_bill' => PurchaseBill::class,
            'credit_note' => CreditNote::class,
            'debit_note' => DebitNote::class,
        ]);

        Gate::policy(Business::class, BusinessPolicy::class);
        Gate::policy(Account::class, AccountPolicy::class);
        Gate::policy(Party::class, PartyPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(SalesInvoice::class, SalesInvoicePolicy::class);
        Gate::policy(CreditNote::class, CreditNotePolicy::class);
        Gate::policy(PurchaseBill::class, PurchaseBillPolicy::class);
        Gate::policy(DebitNote::class, DebitNotePolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Expense::class, ExpensePolicy::class);
        Gate::policy(FinancialYear::class, FinancialYearPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);

        foreach (Permissions::all() as $permission) {
            Gate::define($permission, function (User $user) use ($permission): bool {
                return app(BusinessContext::class)->userCan($permission);
            });
        }

        View::composer(['layouts.app', 'layouts.guest'], function (ViewInstance $view): void {
            if (! $view->offsetExists('pageTitle')) {
                $view->with('pageTitle', RouteTitles::forRoute(request()->route()));
            }
        });
    }
}
