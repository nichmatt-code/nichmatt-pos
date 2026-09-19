<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    /**
     * Search-as-you-type customer/member lookup for the cart's "nama
     * customer" field - mirrors Livewire\Pos\Terminal::
     * getCustomerMatchesProperty() (name OR phone, limit 5). Any
     * authenticated store user can use this (no `permission:customers`
     * gate) since it's needed at checkout, not just by staff who manage
     * the customer list.
     */
    public function search(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->trim()->toString();

        $customers = Customer::query()
            ->when($search !== '', fn ($query) => $query->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
            ))
            ->orderBy('name')
            ->limit(5)
            ->get();

        return CustomerResource::collection($customers);
    }

    /**
     * Paginated, searchable customer list for the "Pelanggan" management
     * screen - mirrors Livewire\Customers\Index::render() (search by
     * name/phone, `withCount('transactions')`, 15 per page, ordered by
     * name).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->trim()->toString();

        $customers = Customer::query()
            ->withCount('transactions')
            ->when($search !== '', fn ($query) => $query->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
            ))
            ->orderBy('name')
            ->paginate(15);

        return CustomerResource::collection($customers);
    }

    public function show(Customer $customer): CustomerResource
    {
        return new CustomerResource($customer->loadCount('transactions'));
    }

    /**
     * Create a customer - same validation as Livewire\Customers\Index::
     * save() (phone unique per store, birthdate must be before today,
     * empty optional fields saved as null).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validated($request);

        $customer = Customer::create($validated);

        return (new CustomerResource($customer->loadCount('transactions')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Customer $customer): CustomerResource
    {
        $validated = $this->validated($request, $customer->id);

        $customer->update($validated);

        return new CustomerResource($customer->loadCount('transactions'));
    }

    /**
     * Hard delete, same as the web version - a customer's past
     * transactions keep their own row (`customer_id` just gets nulled via
     * the FK's `nullOnDelete`), nothing else needs cleaning up here.
     */
    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();

        return response()->json(['message' => 'OK']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $storeId = Auth::user()->store_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required', 'string', 'max:50',
                Rule::unique('customers', 'phone')->where('store_id', $storeId)->ignore($ignoreId),
            ],
            'address' => ['nullable', 'string', 'max:500'],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['address'] = ($validated['address'] ?? '') !== '' ? $validated['address'] : null;
        $validated['birthdate'] = ($validated['birthdate'] ?? '') !== '' ? $validated['birthdate'] : null;
        $validated['notes'] = ($validated['notes'] ?? '') !== '' ? $validated['notes'] : null;

        return $validated;
    }
}
