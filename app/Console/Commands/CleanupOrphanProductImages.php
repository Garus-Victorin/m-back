<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupOrphanProductImages extends Command
{
    protected $signature = 'marketify:cleanup-orphan-images
                            {--dry-run : Run without actually deleting files}
                            {--force : Force cleanup without confirmation}';

    protected $description = 'Clean up orphan product images (images without associated product)';

    public function handle(): int
    {
        if (! $this->option('force')) {
            if (! $this->confirm('This will delete orphan product images. Are you sure you want to continue?')) {
                $this->info('Cleanup cancelled.');
                return self::SUCCESS;
            }
        }

        $dryRun = $this->option('dry-run');
        $deletedCount = 0;
        $errorCount = 0;

        $this->info('Starting orphan product images cleanup...');

        // Trouver les images orphelines
        $orphanImages = ProductImage::query()
            ->whereDoesntHave('product')
            ->get();

        if ($orphanImages->isEmpty()) {
            $this->info('No orphan product images found.');
            return self::SUCCESS;
        }

        $this->info("Found {$orphanImages->count()} orphan product image(s).");

        foreach ($orphanImages as $image) {
            try {
                if ($dryRun) {
                    $this->line("[DRY RUN] Would delete image #{$image->id}: {$image->path}");
                    continue;
                }

                // Supprimer le fichier physique
                if (Storage::disk($image->disk)->exists($image->path)) {
                    Storage::disk($image->disk)->delete($image->path);
                    $this->info("Deleted file: {$image->path}");
                }

                // Supprimer l'enregistrement de la base de données
                $image->delete();
                $deletedCount++;

                Log::info('Deleted orphan product image', [
                    'image_id' => $image->id,
                    'path' => $image->path,
                    'disk' => $image->disk,
                ]);
            } catch (\Exception $e) {
                $this->error("Failed to delete image #{$image->id}: {$e->getMessage()}");
                Log::error('Failed to delete orphan product image', [
                    'image_id' => $image->id,
                    'error' => $e->getMessage(),
                ]);
                $errorCount++;
            }
        }

        $this->info("Cleanup completed. Deleted: {$deletedCount}, Errors: {$errorCount}");

        return $errorCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}
