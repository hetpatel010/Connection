<?php

namespace App\Http\Controllers\Masters;

use App\Application\Services\PartyService;
use App\Domain\Shared\Exceptions\PartyHasTransactionsException;
use App\Http\Controllers\Controller;
use App\Models\Party;
use Illuminate\Http\RedirectResponse;

class CustomerController extends Controller
{
    public function destroy(Party $customer, PartyService $partyService): RedirectResponse
    {
        abort_unless($customer->party_type->isCustomer(), 404);

        $this->authorize('delete', $customer);

        try {
            $partyService->delete($customer);
        } catch (PartyHasTransactionsException) {
            return redirect()->route('masters.customers.index')
                ->with('error', __('This customer cannot be deleted because it has existing transactions.'));
        }

        return redirect()->route('masters.customers.index')
            ->with('status', __('Customer deleted successfully.'));
    }
}
