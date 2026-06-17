<?php

namespace App\Http\Resources\Seller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerKycSubmissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isAdmin = $request->user()?->role === 'admin';
        $sellerFrontDownloadUrl = route('seller.kyc.files.download', ['side' => 'front'], false);
        $sellerBackDownloadUrl = route('seller.kyc.files.download', ['side' => 'back'], false);
        $adminFrontDownloadUrl = route('admin.kyc.files.download', ['submission' => $this->id, 'side' => 'front'], false);
        $adminBackDownloadUrl = route('admin.kyc.files.download', ['submission' => $this->id, 'side' => 'back'], false);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'shop_id' => $this->shop_id,
            'document_type' => $this->document_type,
            'document_number' => $this->document_number,
            'document_front_path' => $this->document_front_path,
            'document_back_path' => $this->document_back_path,
            'document_front_download_url' => $this->document_front_path ? ($isAdmin ? $adminFrontDownloadUrl : $sellerFrontDownloadUrl) : null,
            'document_back_download_url' => $this->document_back_path ? ($isAdmin ? $adminBackDownloadUrl : $sellerBackDownloadUrl) : null,
            'mobile_money_provider' => $this->mobile_money_provider,
            'mobile_money_number' => $this->mobile_money_number,
            'notes' => $this->notes,
            'status' => $this->status,
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at,
            'rejection_reason' => $this->rejection_reason,
            'user' => $this->whenLoaded('user', fn (): array => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'role' => $this->user->role,
            ]),
            'shop' => $this->whenLoaded('shop', fn (): array => [
                'id' => $this->shop->id,
                'name' => $this->shop->name,
                'slug' => $this->shop->slug,
                'status' => $this->shop->status,
            ]),
            'reviewer' => $this->whenLoaded('reviewer', fn (): ?array => $this->reviewer ? [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
                'email' => $this->reviewer->email,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
