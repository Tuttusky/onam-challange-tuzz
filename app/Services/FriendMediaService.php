<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\ChallengeLink;
use App\Models\FriendAvatar;
use App\Models\FriendMedia;
use App\Services\WebsiteSettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use InvalidArgumentException;
use RuntimeException;

class FriendMediaService
{
    protected string $disk = 'local';

    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        return WebsiteSettingsService::getFriendChallengeSettings();
    }

    public function storeUpload(UploadedFile $file, ?int $playerId = null): FriendMedia
    {
        $settings = $this->getSettings();

        if (! ($settings['enable_photo_upload'] ?? true)) {
            throw new RuntimeException('Photo upload is disabled.');
        }

        $maxMb = (int) ($settings['max_image_size_mb'] ?? 5);
        $maxBytes = $maxMb * 1024 * 1024;
        $allowedMimes = $settings['allowed_image_types'] ?? ['image/jpeg', 'image/png', 'image/webp'];

        if ($file->getSize() > $maxBytes) {
            throw new InvalidArgumentException("Image must be smaller than {$maxMb}MB.");
        }

        $mime = $file->getMimeType() ?: $file->getClientMimeType();

        if (! in_array($mime, $allowedMimes, true)) {
            throw new InvalidArgumentException('Unsupported image type.');
        }

        if (! $this->isValidImage($file->getRealPath())) {
            throw new InvalidArgumentException('Invalid image file.');
        }

        $manager = new ImageManager(new Driver);
        $image = $manager->read($file->getRealPath());
        $image->scaleDown(width: 800, height: 800);

        $filename = Str::uuid().'.jpg';
        $path = 'friend-media/'.$filename;
        $encoded = $image->toJpeg(quality: 80);

        Storage::disk($this->disk)->put($path, (string) $encoded);

        $expiryHours = (int) ($settings['media_expiry_hours'] ?? 0);

        return FriendMedia::query()->create([
            'type' => 'upload',
            'storage_path' => $path,
            'mime' => 'image/jpeg',
            'size' => strlen((string) $encoded),
            'width' => $image->width(),
            'height' => $image->height(),
            'player_id' => $playerId,
            'expires_at' => $expiryHours > 0 ? now()->addHours($expiryHours) : null,
        ]);
    }

    public function storeAvatar(int $avatarId, ?int $playerId = null): FriendMedia
    {
        $settings = $this->getSettings();

        if (! ($settings['enable_avatar_selection'] ?? true)) {
            throw new RuntimeException('Avatar selection is disabled.');
        }

        $avatar = FriendAvatar::query()
            ->where('is_active', true)
            ->findOrFail($avatarId);

        return FriendMedia::query()->create([
            'type' => 'avatar',
            'friend_avatar_id' => $avatar->id,
            'storage_path' => $avatar->path,
            'mime' => 'image/png',
            'player_id' => $playerId,
        ]);
    }

    public function storeInitial(string $initial, ?int $playerId = null): FriendMedia
    {
        $initial = mb_strtoupper(mb_substr(trim($initial), 0, 2));

        if ($initial === '') {
            throw new InvalidArgumentException('Initial is required.');
        }

        return FriendMedia::query()->create([
            'type' => 'initial',
            'initial' => $initial,
            'player_id' => $playerId,
        ]);
    }

    public function signedUrl(FriendMedia $media, int $minutes = 60): ?string
    {
        if ($media->isExpired()) {
            return null;
        }

        if ($media->type === 'initial') {
            return null;
        }

        if ($media->type === 'avatar') {
            $path = $media->avatar?->path ?? $media->storage_path;

            return $path ? asset($path) : null;
        }

        if (! $media->storage_path || ! Storage::disk($this->disk)->exists($media->storage_path)) {
            return null;
        }

        return route('api.friend-media.show', [
            'token' => $media->public_token,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(?FriendMedia $media): ?array
    {
        if (! $media || $media->isExpired()) {
            return null;
        }

        return [
            'uuid' => $media->uuid,
            'type' => $media->type,
            'initial' => $media->initial,
            'url' => $this->signedUrl($media),
            'width' => $media->width,
            'height' => $media->height,
        ];
    }

    protected function isValidImage(string $path): bool
    {
        $info = @getimagesize($path);

        return $info !== false && in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true);
    }
}
