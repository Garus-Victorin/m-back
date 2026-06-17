<?php

namespace App\Http\Controllers\Api\Seller;

use App\Actions\Seller\SubmitSellerShopForReviewAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\StoreShopRequest;
use App\Http\Requests\Seller\UpdateShopRequest;
use App\Http\Resources\ShopResource;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ShopController extends Controller
{
    public function store(StoreShopRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->shop()->exists()) {
            throw ValidationException::withMessages([
                'shop' => ['This seller already has a shop.'],
            ]);
        }

        $shop = Shop::create([
            'user_id' => $user->id,
            'name' => $request->string('name')->toString(),
            'slug' => $this->generateUniqueSlug($request->string('name')->toString()),
            'description' => $request->input('description'),
            'phone' => $request->input('phone'),
            'email' => $request->input('email'),
            'address' => $request->input('address'),
            'city' => $request->input('city'),
            'status' => 'draft',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shop created successfully.',
            'data' => [
                'shop' => ShopResource::make($shop),
            ],
        ], 201);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $shop = $user->shop()->first();

        abort_unless($shop, 404, 'Shop not found.');
        $this->authorize('view', $shop);

        return response()->json([
            'success' => true,
            'message' => 'Shop retrieved successfully.',
            'data' => [
                'shop' => ShopResource::make($shop),
            ],
        ]);
    }

    public function update(UpdateShopRequest $request, Shop $shop): JsonResponse
    {
        $this->authorize('update', $shop);

        $attributes = $request->validated();

        if (array_key_exists('name', $attributes) && $attributes['name'] !== $shop->name) {
            $attributes['slug'] = $this->generateUniqueSlug($attributes['name'], $shop->id);
        }

        $shop->update($attributes);

        return response()->json([
            'success' => true,
            'message' => 'Shop updated successfully.',
            'data' => [
                'shop' => ShopResource::make($shop->fresh()),
            ],
        ]);
    }

    public function submitReview(Request $request, SubmitSellerShopForReviewAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $shop = $user->shop()->first();

        abort_unless($shop, 404, 'Shop not found.');
        $this->authorize('update', $shop);

        $shop = $action->execute($user, $shop);

        return response()->json([
            'success' => true,
            'message' => 'Shop submitted for review successfully.',
            'data' => [
                'shop' => ShopResource::make($shop),
            ],
        ]);
    }

    protected function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (Shop::query()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
