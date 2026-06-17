<?php

use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\ShopController as AdminShopController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\Seller\ProductController as SellerProductController;
use App\Http\Controllers\Api\Seller\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'success' => true,
    'message' => 'Marketify API is up.',
]));

Route::prefix('v1')->group(function (): void {
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

    Route::prefix('auth')->group(function (): void {
            Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
            Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

            Route::middleware('auth:sanctum')->group(function (): void {
                Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
                Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
                Route::patch('/password', [AuthController::class, 'updatePassword'])->name('auth.password.update');
            });
        });

        Route::middleware('auth:sanctum')->prefix('seller')->group(function (): void {
            Route::post('/shops', [ShopController::class, 'store'])->name('seller.shops.store');
            Route::get('/shops/me', [ShopController::class, 'me'])->name('seller.shops.me');
            Route::patch('/shops/{shop}', [ShopController::class, 'update'])->name('seller.shops.update');

            Route::get('/products', [SellerProductController::class, 'index'])->name('seller.products.index');
            Route::post('/products', [SellerProductController::class, 'store'])->name('seller.products.store');
            Route::patch('/products/{product}', [SellerProductController::class, 'update'])->name('seller.products.update');
            Route::delete('/products/{product}', [SellerProductController::class, 'destroy'])->name('seller.products.destroy');
        });

        Route::middleware('auth:sanctum')->prefix('admin')->group(function (): void {
            Route::get('/categories', [AdminCategoryController::class, 'index'])->name('admin.categories.index');
            Route::post('/categories', [AdminCategoryController::class, 'store'])->name('admin.categories.store');
            Route::patch('/categories/{category}', [AdminCategoryController::class, 'update'])->name('admin.categories.update');
            Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('admin.categories.destroy');

            Route::get('/shops', [AdminShopController::class, 'index'])->name('admin.shops.index');
            Route::get('/shops/{shop}', [AdminShopController::class, 'show'])->name('admin.shops.show');
            Route::patch('/shops/{shop}', [AdminShopController::class, 'update'])->name('admin.shops.update');

            Route::get('/products', [AdminProductController::class, 'index'])->name('admin.products.index');
            Route::get('/products/{product}', [AdminProductController::class, 'show'])->name('admin.products.show');
            Route::post('/products', [AdminProductController::class, 'store'])->name('admin.products.store');
            Route::patch('/products/{product}', [AdminProductController::class, 'update'])->name('admin.products.update');
            Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('admin.products.destroy');
        });
});
