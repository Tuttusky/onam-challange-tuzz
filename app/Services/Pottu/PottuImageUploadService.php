<?php

namespace App\Services\Pottu;

use App\Models\Campaign;
use App\Models\PottuImage;
use App\Services\Pottu\PottuCustomImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use InvalidArgumentException;

class PottuImageUploadService
{
    public function storeCustomImage(Campaign $campaign, UploadedFile $file): PottuImage
    {
        if (! $this->isValidImage($file->getRealPath())) {
            throw new InvalidArgumentException('Invalid image file.');
        }

        if (extension_loaded('gd')) {
            return $this->storeCompressedImage($campaign, $file);
        }

        return $this->storeOriginalImage($campaign, $file);
    }

    protected function storeCompressedImage(Campaign $campaign, UploadedFile $file): PottuImage
    {
        $manager = new ImageManager(new Driver);
        $image = $manager->read($file->getRealPath());
        $image->scaleDown(width: 900, height: 1200);

        $filename = Str::uuid().'.jpg';
        $relativePath = 'pottu-custom-images/'.$filename;
        $encoded = $image->toJpeg(quality: 82);

        Storage::disk('public')->put($relativePath, (string) $encoded);

        return PottuImage::query()->create([
            'campaign_id' => $campaign->id,
            'title' => 'Custom Image',
            'path' => $relativePath,
            'width' => $image->width(),
            'height' => $image->height(),
            'sort_order' => 999,
            'is_active' => false,
            'is_custom' => true,
            'expires_at' => PottuCustomImageService::expiresAt(),
        ]);
    }

    protected function storeOriginalImage(Campaign $campaign, UploadedFile $file): PottuImage
    {
        $relativePath = $file->store('pottu-custom-images', 'public');
        $info = @getimagesize($file->getRealPath());

        return PottuImage::query()->create([
            'campaign_id' => $campaign->id,
            'title' => 'Custom Image',
            'path' => $relativePath,
            'width' => $info[0] ?? 600,
            'height' => $info[1] ?? 900,
            'sort_order' => 999,
            'is_active' => false,
            'is_custom' => true,
            'expires_at' => PottuCustomImageService::expiresAt(),
        ]);
    }

    protected function isValidImage(string $path): bool
    {
        $info = @getimagesize($path);

        return $info !== false && in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true);
    }
}
