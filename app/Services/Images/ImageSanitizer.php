<?php

namespace App\Services\Images;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImageSanitizer
{
    /**
     * Re-encode the uploaded image to strip EXIF metadata and persist it.
     * Returns the storage path relative to the disk, or null on failure.
     */
    public function storeSanitized(UploadedFile|TemporaryUploadedFile $file, string $directory, string $disk): ?string
    {
        $mime = $file->getMimeType();
        if (! $mime || ! str_starts_with($mime, 'image/')) {
            return null;
        }

        $raw = file_get_contents($file->getRealPath());
        if ($raw === false) {
            return null;
        }

        $image = @imagecreatefromstring($raw);
        if ($image === false) {
            return null;
        }

        $extension = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $name = Str::uuid()->toString().'.'.$extension;
        $relativePath = trim($directory, '/').'/'.$name;

        $tempPath = tempnam(sys_get_temp_dir(), 'sanitized-').'.'.$extension;

        $written = match ($mime) {
            'image/png' => imagepng($image, $tempPath, 6),
            'image/webp' => imagewebp($image, $tempPath, 82),
            default => imagejpeg($image, $tempPath, 85),
        };

        imagedestroy($image);

        if (! $written) {
            @unlink($tempPath);
            return null;
        }

        $stream = fopen($tempPath, 'rb');
        if ($stream === false) {
            @unlink($tempPath);
            return null;
        }

        try {
            Storage::disk($disk)->put($relativePath, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
            @unlink($tempPath);
        }

        return $relativePath;
    }
}
