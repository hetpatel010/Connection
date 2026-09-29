<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Concerns\RespondsWithJsonEnvelope;
use App\Http\Controllers\Controller;
use App\Models\Party;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartyController extends Controller
{
    use RespondsWithJsonEnvelope;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Party::class);

        $customers = Party::query()
            ->customers()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = (string) $request->string('search');

                $query->where(function ($builder) use ($search): void {
                    $builder->where('name', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('gstin', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->success(
            $customers->getCollection()->map(fn (Party $party): array => $this->transform($party))->values(),
            [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ],
        );
    }

    public function show(Party $party): JsonResponse
    {
        abort_unless($party->party_type->isCustomer(), 404);

        $this->authorize('view', $party);

        return $this->success($this->transform($party));
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(Party $party): array
    {
        return [
            'id' => $party->id,
            'party_type' => $party->party_type->value,
            'name' => $party->name,
            'legal_name' => $party->legal_name,
            'phone' => $party->phone,
            'email' => $party->email,
            'gstin' => $party->gstin,
            'pan' => $party->pan,
            'billing_city' => $party->billing_city,
            'billing_state' => $party->billing_state,
            'status' => $party->status->value,
            'opening_balance' => $party->opening_balance,
            'opening_balance_side' => $party->opening_balance_side?->value,
            'created_at' => $party->created_at?->toIso8601String(),
            'updated_at' => $party->updated_at?->toIso8601String(),
        ];
    }
}
