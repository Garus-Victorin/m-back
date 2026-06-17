<?php

namespace App\Actions\Seller;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreSellerKycDocumentAction
{
    /**
     * @return array{disk: string, path: string, original_name: string, mime_type: string, size: int}
     */
    public function execute(User $user, UploadedFile $file, string $side): array
    {
        $extension = strtolower($file->guessExtension() ?: $file->extension() ?: 'bin');
        $path = sprintf(
            'kyc/%d/%s_%s.%s',
            $user->id,
            Str::lower((string) Str::uuid()),
            $side,
            $extension,
        );

        Storage::disk('local')->putFileAs(
            dirname($path),
            $file,
            basename($path),
        );

        return [
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => (int) $file->getSize(),
        ];
    }
}
