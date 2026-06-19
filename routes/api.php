<?php

use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\FinanceController as AdminFinanceController;
use App\Http\Controllers\Api\Admin\KycController as AdminKycController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\ShopController as AdminShopController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\Seller\DashboardController as SellerDashboardController;
use App\Http\Controllers\Api\Seller\FinanceController as SellerFinanceController;
use App\Http\Controllers\Api\Seller\KycController as SellerKycController;
use App\Http\Controllers\Api\Seller\OrderController as SellerOrderController;
use App\Http\Controllers\Api\Seller\ProductController as SellerProductController;
use App\Http\Controllers\Api\Seller\SettingsController as SellerSettingsController;
use App\Http\Controllers\Api\Seller\ShopController as SellerShopController;
use Illuminate\Support\Facades\Route;

Route::get(
    "/health",
    fn() => response()->json([
        "success" => true,
        "message" => "Marketify API is up.",
    ]),
);

Route::prefix("v1")->group(function (): void {
    Route::get("/categories", [CategoryController::class, "index"])->name(
        "categories.index",
    );
    Route::get("/products", [ProductController::class, "index"])->name(
        "products.index",
    );
    Route::get("/products/{product:slug}", [
        ProductController::class,
        "show",
    ])->name("products.show");

    Route::prefix("auth")->group(function (): void {
        Route::post("/register", [AuthController::class, "register"])->name(
            "auth.register",
        );
        Route::post("/login", [AuthController::class, "login"])->name(
            "auth.login",
        );

        Route::middleware("auth:sanctum")->group(function (): void {
            Route::post("/logout", [AuthController::class, "logout"])->name(
                "auth.logout",
            );
            Route::get("/me", [AuthController::class, "me"])->name("auth.me");
            Route::patch("/password", [
                AuthController::class,
                "updatePassword",
            ])->name("auth.password.update");
        });
    });

    Route::middleware("auth:sanctum")->group(function (): void {
        Route::post("/orders", [OrderController::class, "store"])->name(
            "orders.store",
        );
    });

    Route::middleware(["auth:sanctum", "seller.active", "throttle:seller"])
        ->prefix("seller")
        ->group(function (): void {
            Route::get("/bootstrap", [
                SellerDashboardController::class,
                "bootstrap",
            ])
                ->name("seller.bootstrap")
                ->middleware("seller.rate_limit:bootstrap");
            Route::get("/me", [
                \App\Http\Controllers\Api\Seller\MeController::class,
                "__invoke",
            ])->name("seller.me");
            Route::get("/dashboard", [
                SellerDashboardController::class,
                "show",
            ])->name("seller.dashboard");

            Route::post("/shops", [SellerShopController::class, "store"])->name(
                "seller.shops.store",
            );
            Route::get("/shops/me", [SellerShopController::class, "me"])->name(
                "seller.shops.me",
            );
            Route::patch("/shops/{shop}", [
                SellerShopController::class,
                "update",
            ])->name("seller.shops.update");
            Route::post("/shops/submit-review", [
                SellerShopController::class,
                "submitReview",
            ])->name("seller.shops.submit-review");

            Route::post("/shops/logo", [
                ShopBrandingController::class,
                "uploadLogo",
            ])
                ->name("seller.shops.logo.upload")
                ->middleware("seller.rate_limit:uploads");
            Route::post("/shops/banner", [
                ShopBrandingController::class,
                "uploadBanner",
            ])->name("seller.shops.banner.upload");
            Route::get("/shops/branding", [
                ShopBrandingController::class,
                "show",
            ])->name("seller.shops.branding.show");

            Route::get("/kyc", [SellerKycController::class, "show"])->name(
                "seller.kyc.show",
            );
            Route::post("/kyc", [SellerKycController::class, "store"])->name(
                "seller.kyc.store",
            );
            Route::post("/kyc/uploads/{side}", [
                SellerKycController::class,
                "uploadDocument",
            ])
                ->whereIn("side", ["front", "back"])
                ->name("seller.kyc.upload");
            Route::post("/kyc/selfie", [
                KycSelfieController::class,
                "store",
            ])->name("seller.kyc.selfie.store");
            Route::get("/kyc/selfie", [
                KycSelfieController::class,
                "show",
            ])->name("seller.kyc.selfie.show");
            Route::get("/kyc/files/{side}", [
                SellerKycController::class,
                "downloadFile",
            ])
                ->whereIn("side", ["front", "back", "selfie"])
                ->name("seller.kyc.files.download");

            Route::get("/notifications", [
                NotificationController::class,
                "index",
            ])->name("seller.notifications.index");
            Route::post("/notifications/{notification}/read", [
                NotificationController::class,
                "markAsRead",
            ])->name("seller.notifications.mark_as_read");
            Route::post("/notifications/read-all", [
                NotificationController::class,
                "markAllAsRead",
            ])->name("seller.notifications.mark_all_as_read");
            Route::delete("/notifications/{notification}", [
                NotificationController::class,
                "destroy",
            ])->name("seller.notifications.destroy");

            Route::get("/onboarding", [
                OnboardingController::class,
                "show",
            ])->name("seller.onboarding.show");
            Route::post("/onboarding", [
                OnboardingController::class,
                "complete",
            ])->name("seller.onboarding.complete");

            Route::get("/finance/summary", [
                SellerFinanceController::class,
                "summary",
            ])->name("seller.finance.summary");
            Route::get("/finance/transactions", [
                TransactionController::class,
                "index",
            ])->name("seller.finance.transactions");
            Route::get("/finance/withdrawals", [
                SellerFinanceController::class,
                "withdrawals",
            ])->name("seller.finance.withdrawals");
            Route::post("/finance/withdrawals", [
                SellerFinanceController::class,
                "storeWithdrawal",
            ])
                ->name("seller.finance.withdrawals.store")
                ->middleware("seller.rate_limit:withdrawals");
            Route::post("/finance/withdrawals/{withdrawal}/process", [
                \App\Http\Controllers\Api\Seller\PayoutController::class,
                "processWithdrawal",
            ])->name("seller.finance.withdrawals.process");
            Route::post("/finance/withdrawals/{withdrawal}/callback", [
                \App\Http\Controllers\Api\Seller\PayoutController::class,
                "callback",
            ])->name("api.seller.payout.callback");

            Route::get("/settings", [
                SellerSettingsController::class,
                "show",
            ])->name("seller.settings.show");
            Route::patch("/settings", [
                SellerSettingsController::class,
                "update",
            ])->name("seller.settings.update");

            Route::get("/products", [SellerProductController::class, "index"])
                ->name("seller.products.index")
                ->middleware("seller.rate_limit:products_read");
            Route::post("/products", [SellerProductController::class, "store"])
                ->name("seller.products.store")
                ->middleware("seller.rate_limit:products_write");
            Route::get("/products/{product}", [
                SellerProductController::class,
                "show",
            ])->name("seller.products.show");
            Route::patch("/products/{product}", [
                SellerProductController::class,
                "update",
            ])->name("seller.products.update");
            Route::patch("/products/{product}/stock", [
                SellerProductController::class,
                "updateStock",
            ])->name("seller.products.stock.update");
            Route::put("/products/{product}/variants", [
                SellerProductController::class,
                "replaceVariants",
            ])->name("seller.products.variants.replace");
            Route::post("/products/{product}/submit-review", [
                SellerProductController::class,
                "submitReview",
            ])->name("seller.products.submit-review");
            Route::post("/products/{product}/archive", [
                SellerProductController::class,
                "archive",
            ])->name("seller.products.archive");
            Route::post("/products/{product}/restore", [
                SellerProductController::class,
                "restore",
            ])->name("seller.products.restore");
            Route::post("/products/{product}/images", [
                SellerProductController::class,
                "storeImage",
            ])->name("seller.products.images.store");
            Route::patch("/products/{product}/images/reorder", [
                SellerProductController::class,
                "reorderImages",
            ])->name("seller.products.images.reorder");
            Route::post("/products/{product}/cover/{image}", [
                ProductCoverController::class,
                "setCover",
            ])->name("seller.products.cover.set");
            Route::delete("/products/{product}/cover", [
                ProductCoverController::class,
                "removeCover",
            ])->name("seller.products.cover.remove");
            Route::delete("/products/{product}/images/{image}", [
                SellerProductController::class,
                "destroyImage",
            ])->name("seller.products.images.destroy");
            Route::get("/products/{product}/images/{image}/download", [
                SellerProductController::class,
                "downloadImage",
            ])->name("seller.products.images.download");
            Route::delete("/products/{product}", [
                SellerProductController::class,
                "destroy",
            ])->name("seller.products.destroy");

            Route::get("/orders", [SellerOrderController::class, "index"])
                ->name("seller.orders.index")
                ->middleware("seller.rate_limit:orders");
            Route::get("/orders/{order}", [
                SellerOrderController::class,
                "show",
            ])->name("seller.orders.show");
            Route::post("/orders/{order}/mark-ready", [
                SellerOrderController::class,
                "markReady",
            ])->name("seller.orders.mark-ready");
            Route::post("/orders/{order}/cancel-request", [
                SellerOrderController::class,
                "cancelRequest",
            ])->name("seller.orders.cancel-request");
            Route::get("/orders/{order}/packing-slip", [
                SellerOrderController::class,
                "packingSlip",
            ])->name("seller.orders.packing-slip");
        });

    Route::middleware("auth:sanctum")
        ->prefix("admin")
        ->group(function (): void {
            Route::get("/categories", [
                AdminCategoryController::class,
                "index",
            ])->name("admin.categories.index");
            Route::post("/categories", [
                AdminCategoryController::class,
                "store",
            ])->name("admin.categories.store");
            Route::patch("/categories/{category}", [
                AdminCategoryController::class,
                "update",
            ])->name("admin.categories.update");
            Route::delete("/categories/{category}", [
                AdminCategoryController::class,
                "destroy",
            ])->name("admin.categories.destroy");

            Route::get("/shops", [AdminShopController::class, "index"])->name(
                "admin.shops.index",
            );
            Route::get("/shops/{shop}", [
                AdminShopController::class,
                "show",
            ])->name("admin.shops.show");
            Route::patch("/shops/{shop}", [
                AdminShopController::class,
                "update",
            ])->name("admin.shops.update");
            Route::post("/shops/{shop}/reject", [
                ShopRejectionController::class,
                "reject",
            ])->name("admin.shops.reject");

            Route::get("/shops/{shop}/rejection", [
                ShopRejectionController::class,
                "showRejection",
            ])->name("admin.shops.rejection.show");

            Route::get("/kyc-submissions", [
                AdminKycController::class,
                "index",
            ])->name("admin.kyc.index");
            Route::get("/kyc-submissions/{submission}", [
                AdminKycController::class,
                "show",
            ])->name("admin.kyc.show");
            Route::patch("/kyc-submissions/{submission}", [
                AdminKycController::class,
                "review",
            ])->name("admin.kyc.review");
            Route::get("/kyc-submissions/{submission}/files/{side}", [
                AdminKycController::class,
                "downloadFile",
            ])
                ->whereIn("side", ["front", "back"])
                ->name("admin.kyc.files.download");

            Route::get("/products", [
                AdminProductController::class,
                "index",
            ])->name("admin.products.index");
            Route::get("/products/{product}", [
                AdminProductController::class,
                "show",
            ])->name("admin.products.show");
            Route::post("/products", [
                AdminProductController::class,
                "store",
            ])->name("admin.products.store");
            Route::patch("/products/{product}", [
                AdminProductController::class,
                "update",
            ])->name("admin.products.update");
            Route::patch("/products/{product}/review", [
                AdminProductController::class,
                "review",
            ])->name("admin.products.review");
            Route::get("/products/{product}/images/{image}/download", [
                AdminProductController::class,
                "downloadImage",
            ])->name("admin.products.images.download");
            Route::delete("/products/{product}", [
                AdminProductController::class,
                "destroy",
            ])->name("admin.products.destroy");

            Route::get("/finance/withdrawals", [
                AdminFinanceController::class,
                "withdrawals",
            ])->name("admin.finance.withdrawals.index");
            Route::get("/finance/withdrawals/{withdrawal}", [
                AdminFinanceController::class,
                "showWithdrawal",
            ])->name("admin.finance.withdrawals.show");
            Route::patch("/finance/withdrawals/{withdrawal}", [
                AdminFinanceController::class,
                "updateWithdrawal",
            ])->name("admin.finance.withdrawals.update");
        });
});
