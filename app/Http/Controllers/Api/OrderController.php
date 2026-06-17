<?php

namespace App\Http\Controllers\Api;

use App\Actions\Order\CreateCustomerOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request, CreateCustomerOrderAction $action): JsonResponse
    {
        /** @var User $customer */
        $customer = $request->user();

        $this->ensureCustomer($customer);

        $order = $action->execute($customer, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully.',
            'data' => [
                'order' => OrderResource::make($order),
            ],
        ], 201);
    }

    protected function ensureCustomer(?User $user): void
    {
        abort_unless($user && $user->role === 'customer', 403, 'Only customers can perform this action.');
        abort_unless($user->is_active, 403, 'Customer account is inactive.');
    }
}
