<?php

namespace App\Http\Controllers\Api\Seller;

use App\Actions\Audit\RecordAuditLogAction;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Traits\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ProductCoverController extends Controller
{
    public function setCover(
        Request $request,
        Product $product,
        ProductImage $image,
        RecordAuditLogAction $auditLog,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $this->authorize("update", $product);

        // Vérifier que l'image appartient au produit
        if ($image->product_id !== $product->id) {
            throw new ConflictHttpException(
                "The specified image does not belong to this product.",
            );
        }

        // Mettre à jour l'image de couverture
        $product->update(["cover_image_id" => $image->id]);

        $auditLog->execute(
            action: "seller.product.cover_set",
            actor: $user,
            target: $product,
            after: [
                "cover_image_id" => $image->id,
                "cover_image_position" => $image->position,
            ],
            request: $request,
        );

        return response()->json([
            "success" => true,
            "message" => "Product cover image set successfully.",
            "data" => [
                "product_id" => $product->id,
                "cover_image_id" => $image->id,
                "cover_image_url" => $image->url,
            ],
        ]);
    }

    public function removeCover(
        Request $request,
        Product $product,
        RecordAuditLogAction $auditLog,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $this->authorize("update", $product);

        if ($product->cover_image_id === null) {
            throw new ConflictHttpException(
                "This product does not have a cover image set.",
            );
        }

        // Supprimer l'image de couverture
        $previousCoverId = $product->cover_image_id;
        $product->update(["cover_image_id" => null]);

        $auditLog->execute(
            action: "seller.product.cover_removed",
            actor: $user,
            target: $product,
            before: [
                "cover_image_id" => $previousCoverId,
            ],
            after: [
                "cover_image_id" => null,
            ],
            request: $request,
        );

        return response()->json([
            "success" => true,
            "message" => "Product cover image removed successfully.",
            "data" => [
                "product_id" => $product->id,
                "cover_image_id" => null,
            ],
        ]);
    }
}
