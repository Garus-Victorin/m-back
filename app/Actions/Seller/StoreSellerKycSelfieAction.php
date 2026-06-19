<?php

namespace App\Actions\Seller;

use App\Models\SellerKycSubmission;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreSellerKycSelfieAction
{
    public function execute(Shop $shop, User $user, UploadedFile $file): array
    {
        try {
            // Vérifier que le shop a déjà une soumission KYC
            $kycSubmission = $shop->kycSubmission;

            if (!$kycSubmission) {
                $kycSubmission = SellerKycSubmission::create([
                    'shop_id' => $shop->id,
                    'user_id' => $user->id,
                    'status' => 'draft',
                    'document_type' => 'id_card',
                ]);
            }

            // Supprimer l'ancien selfie si nécessaire
            if ($kycSubmission->selfie_path && Storage::disk('private')->exists($kycSubmission->selfie_path)) {
                Storage::disk('private')->delete($kycSubmission->selfie_path);
            }

            // Stocker le nouveau selfie
            $path = $file->storeAs(
                "kyc/{$shop->id}",
                'selfie-' . Str::random(16) . '.' . $file->getClientOriginalExtension(),
                'private'
            );

            $kycSubmission->update([
                'selfie_path' => $path,
                'selfie_mime_type' => $file->getClientMimeType(),
                'selfie_size' => $file->getSize(),
            ]);

            // Mettre à jour le statut KYC de l'utilisateur si nécessaire
            if ($kycSubmission->document_front_path && $kycSubmission->document_back_path && $kycSubmission->selfie_path) {
                $kycSubmission->update(['status' => 'pending']);
                $user->update(['kyc_status' => 'pending']);
            }

            return [
                'success' => true,
                'path' => $path,
                'url' => route('admin.kyc.files.download', [
                    'submission' => $kycSubmission->id,
                    'side' => 'selfie',
                ]),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to store KYC selfie: ' . $e->getMessage(),
            ];
        }
    }
}
