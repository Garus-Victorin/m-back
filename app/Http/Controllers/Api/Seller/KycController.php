<?php

namespace App\Http\Controllers\Api\Seller;

use App\Actions\Seller\StoreSellerKycDocumentAction;
use App\Actions\Seller\SubmitSellerKycAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\StoreSellerKycDocumentRequest;
use App\Http\Requests\Seller\StoreSellerKycRequest;
use App\Http\Resources\Seller\SellerKycSubmissionResource;
use App\Models\SellerKycSubmission;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KycController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $shop = $user->shop()->first();

        return response()->json([
            'success' => true,
            'message' => 'Seller KYC retrieved successfully.',
            'data' => [
                'kyc_submission' => $shop?->kycSubmission
                    ? SellerKycSubmissionResource::make($shop->kycSubmission)
                    : null,
            ],
        ]);
    }

    public function store(StoreSellerKycRequest $request, SubmitSellerKycAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $shop = $this->sellerShop($user);

        $submission = $action->execute($user, $shop, $request->validated(), $request);

        return response()->json([
            'success' => true,
            'message' => 'Seller KYC submitted successfully.',
            'data' => [
                'kyc_submission' => SellerKycSubmissionResource::make($submission),
            ],
        ], 201);
    }

    public function uploadDocument(
        StoreSellerKycDocumentRequest $request,
        StoreSellerKycDocumentAction $action,
        string $side,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $this->sellerShop($user);

        abort_unless(in_array($side, ['front', 'back'], true), 404, 'KYC document upload endpoint not found.');

        $stored = $action->execute($user, $request->file('file'), $side);

        return response()->json([
            'success' => true,
            'message' => 'Seller KYC document uploaded successfully.',
            'data' => [
                'file' => $stored,
            ],
        ], 201);
    }

    public function downloadFile(Request $request, string $side): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();
        $shop = $this->sellerShop($user);

        /** @var SellerKycSubmission|null $submission */
        $submission = $shop->kycSubmission;
        abort_unless($submission, 404, 'KYC submission not found.');

        $path = match ($side) {
            'front' => $submission->document_front_path,
            'back' => $submission->document_back_path,
            default => abort(404, 'KYC document file not found.'),
        };

        abort_unless($path && Storage::disk('local')->exists($path), 404, 'KYC document file not found.');

        return Storage::disk('local')->download($path, basename($path));
    }

    protected function sellerShop(User $user): Shop
    {
        return $user->shop()->firstOrFail();
    }
}
