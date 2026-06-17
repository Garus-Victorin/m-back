<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Admin\ReviewSellerKycAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewSellerKycRequest;
use App\Http\Resources\Seller\SellerKycSubmissionResource;
use App\Models\SellerKycSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KycController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureAdmin($request->user());

        $query = SellerKycSubmission::query()
            ->with(['user', 'shop', 'reviewer'])
            ->latest();

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($shopId = $request->integer('shop_id')) {
            $query->where('shop_id', $shopId);
        }

        if ($userId = $request->integer('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($search = trim($request->string('search')->toString())) {
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('document_number', 'like', "%{$search}%")
                    ->orWhere('mobile_money_number', 'like', "%{$search}%")
                    ->orWhereHas('shop', fn (Builder $shopQuery) => $shopQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn (Builder $userQuery) => $userQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $submissions = $query
            ->paginate(min(max($request->integer('per_page', 15), 1), 50))
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Admin seller KYC submissions retrieved successfully.',
            'data' => [
                'kyc_submissions' => SellerKycSubmissionResource::collection($submissions->getCollection()),
                'pagination' => [
                    'current_page' => $submissions->currentPage(),
                    'last_page' => $submissions->lastPage(),
                    'per_page' => $submissions->perPage(),
                    'total' => $submissions->total(),
                ],
            ],
        ]);
    }

    public function show(Request $request, SellerKycSubmission $submission): JsonResponse
    {
        $this->ensureAdmin($request->user());
        $submission->load(['user', 'shop', 'reviewer']);

        return response()->json([
            'success' => true,
            'message' => 'Admin seller KYC submission retrieved successfully.',
            'data' => [
                'kyc_submission' => SellerKycSubmissionResource::make($submission),
            ],
        ]);
    }

    public function review(
        ReviewSellerKycRequest $request,
        SellerKycSubmission $submission,
        ReviewSellerKycAction $action,
    ): JsonResponse {
        /** @var User $admin */
        $admin = $request->user();
        $this->ensureAdmin($admin);

        $submission = $action->execute($admin, $submission, $request->validated(), $request);

        return response()->json([
            'success' => true,
            'message' => 'Admin seller KYC submission reviewed successfully.',
            'data' => [
                'kyc_submission' => SellerKycSubmissionResource::make($submission),
            ],
        ]);
    }

    public function downloadFile(Request $request, SellerKycSubmission $submission, string $side): StreamedResponse
    {
        $this->ensureAdmin($request->user());

        $path = match ($side) {
            'front' => $submission->document_front_path,
            'back' => $submission->document_back_path,
            default => abort(404, 'KYC document file not found.'),
        };

        abort_unless($path && Storage::disk('local')->exists($path), 404, 'KYC document file not found.');

        return Storage::disk('local')->download($path, basename($path));
    }

    protected function ensureAdmin(?User $user): void
    {
        abort_unless($user && $user->role === 'admin', 403, 'Only admins can perform this action.');
        abort_unless($user->is_active, 403, 'Admin account is inactive.');
    }
}
