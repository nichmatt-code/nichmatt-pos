<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    /**
     * Search-as-you-type customer/member lookup for the cart's "nama
     * customer" field - mirrors Livewire\Pos\Terminal::
     * getCustomerMatchesProperty() (name OR phone, limit 5). The mobile
     * app never creates a Customer row itself - picking a match sets
     * `customer_id` at checkout, otherwise `customer_name` is just sent
     * as plain text (same as the web terminal).
     */
    public function index(Request $request): AnonymousResourceCollection
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
}
