<?php

namespace App\Actions\Seller;

use App\Actions\Audit\RecordAuditLogAction;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreProductImageAction
{
    public function __construct(
        protected RecordAuditLogAction $auditLog,
    ) {
    }

    public function execute(User $user, Product $product, UploadedFile $file, ?Request $request = null): ProductImage
    {
        $extension = strtolower($file->guessExtension() ?: $file->extension() ?: 'bin');
        $path = sprintf(
            'products/%d/%d/%s.%s',
            $user->id,
            $product->id,
            Str::lower((string) Str::uuid()),
            $extension,
        );

        Storage::disk('local')->putFileAs(dirname($path), $file, basename($path));

        $image = ProductImage::create([
            'product_id' => $product->id,
            'disk' => 'local',
            'path' => $path,
            'position' => ((int) $product->images()->max('position')) + 1,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => (int) $file->getSize(),
        ]);

        $this->auditLog->execute(
            action: 'seller.product_image.uploaded',
            actor: $user,
            target: $product,
            after: [
                'product_image_id' => $image->id,
                'path' => $image->path,
                'mime_type' => $image->mime_type,
            ],
            request: $request,
        );

        return $image;
    }
}
