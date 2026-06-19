<?php

namespace App\Actions\Seller;

use App\Models\Shop;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class StoreShopBrandingAction
{
    public function execute(Shop $shop, UploadedFile $file, string $type): array
    {
        try {
            // Valider le type
            if (!in_array($type, ['logo', 'banner'])) {
                return [
                    'success' => false,
                    'error' => 'Invalid branding type.',
                ];
            }

            // Générer un nom de fichier unique
            $extension = $file->getClientOriginalExtension();
            $filename = 'shop-' . $shop->id . '-' . $type . '-' . Str::random(16) . '.' . $extension;

            // Chemin de stockage
            $path = "shops/{$shop->id}/branding";
            $fullPath = "{$path}/{$filename}";

            // Créer le répertoire si nécessaire
            Storage::disk('public')->makeDirectory($path);

            // Traiter l'image avec Intervention Image
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file->getRealPath());

            // Redimensionner selon le type
            if ($type === 'logo') {
                $image->resize(500, 500, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            } elseif ($type === 'banner') {
                $image->resize(1200, 400, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            // Optimiser et sauvegarder
            $image->toWebp(90)->save(storage_path("app/public/{$fullPath}"));

            // Mettre à jour le shop
            $updateData = [$type . '_url' => "storage/{$fullPath}"];

            // Supprimer l'ancien fichier si nécessaire
            if ($shop->{$type . '_url'}) {
                $oldPath = str_replace('/storage/', '', $shop->{$type . '_url'});
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $shop->update($updateData);

            return [
                'success' => true,
                'url' => "storage/{$fullPath}",
                'path' => $fullPath,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to process branding: ' . $e->getMessage(),
            ];
        }
    }
}
