<?php

namespace App\Http\Controllers\Masters;

use App\Application\Services\PartyService;
use App\Domain\Shared\Exceptions\PartyHasTransactionsException;
use App\Http\Controllers\Controller;
use App\Models\Party;
use Illuminate\Http\RedirectResponse;

class SupplierController extends Controller
{
    public function destroy(Party $supplier, PartyService $partyService): RedirectResponse
    {
        abort_unless($supplier->party_type->isSupplier(), 404);

        $this->authorize('delete', $supplier);

        try {
            $partyService->delete($supplier);
        } catch (PartyHasTransactionsException) {
            return redirect()->route('masters.suppliers.index')
                ->with('error', __('This supplier cannot be deleted because it has existing transactions.'));
        }

        return redirect()->route('masters.suppliers.index')
            ->with('status', __('Supplier deleted successfully.'));
    }
}
