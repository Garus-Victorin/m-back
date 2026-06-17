<?php

namespace App\Http\Controllers\Api\Seller;

use App\Actions\Seller\UpdateSellerPayoutSettingsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\UpdateSellerSettingsRequest;
use App\Http\Resources\Seller\SellerSettingsResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $shop = $user->shop()->first();

        abort_unless($shop, 404, 'Shop not found.');
        $this->authorize('view', $shop);

        return response()->json([
            'success' => true,
            'message' => 'Seller settings retrieved successfully.',
            'data' => [
                'settings' => SellerSettingsResource::make($shop),
            ],
        ]);
    }

    public function update(UpdateSellerSettingsRequest $request, UpdateSellerPayoutSettingsAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $shop = $user->shop()->first();

        abort_unless($shop, 404, 'Shop not found.');
        $this->authorize('update', $shop);

        $shop = $action->execute($user, $shop, $request->validated(), $request);

        return response()->json([
            'success' => true,
            'message' => 'Seller settings updated successfully.',
            'data' => [
                'settings' => SellerSettingsResource::make($shop),
            ],
        ]);
    }
}
