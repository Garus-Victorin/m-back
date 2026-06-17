<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateShopRequest;
use App\Http\Resources\ShopResource;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShopController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureAdmin($request->user());

        $query = Shop::query()->latest();

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $shops = $query
            ->paginate(min(max($request->integer('per_page', 15), 1), 50))
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Admin shops retrieved successfully.',
            'data' => [
                'shops' => ShopResource::collection($shops->getCollection()),
                'pagination' => [
                    'current_page' => $shops->currentPage(),
                    'last_page' => $shops->lastPage(),
                    'per_page' => $shops->perPage(),
                    'total' => $shops->total(),
                ],
            ],
        ]);
    }

    public function show(Request $request, Shop $shop): JsonResponse
    {
        $this->ensureAdmin($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Admin shop retrieved successfully.',
            'data' => [
                'shop' => ShopResource::make($shop),
            ],
        ]);
    }

    public function update(UpdateShopRequest $request, Shop $shop): JsonResponse
    {
        $this->ensureAdmin($request->user());

        $attributes = $request->validated();

        if (array_key_exists('name', $attributes) && $attributes['name'] !== $shop->name) {
            $attributes['slug'] = $this->generateUniqueSlug($attributes['name'], $shop->id);
        }

        $shop->update($attributes);

        return response()->json([
            'success' => true,
            'message' => 'Admin shop updated successfully.',
            'data' => [
                'shop' => ShopResource::make($shop->fresh()),
            ],
        ]);
    }

    protected function ensureAdmin(?User $user): void
    {
        abort_unless($user && $user->role === 'admin', 403, 'Only admins can perform this action.');
        abort_unless($user->is_active, 403, 'Admin account is inactive.');
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
