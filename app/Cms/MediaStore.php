<?php

namespace App\Cms;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Stores the images uploaded to the media library.
 *
 * When the GD extension is available, each image is re-encoded: metadata
 * (EXIF, GPS position…) is removed, booby-trapped files are neutralised and
 * large pictures are resized.
 */
class MediaStore
{
    public const DISK = 'media';

    public function store(UploadedFile $file, ?string $alt = null): Media
    {
        $info = @getimagesize($file->getRealPath());
        $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];

        if (! $info || ! isset($allowed[$info[2]])) {
            throw new RuntimeException(__('This file is not a valid JPEG, PNG or WebP image.'));
        }

        [$width, $height, $type] = $info;
        $name = Str::lower(Str::random(40));

        if (extension_loaded('gd')) {
            [$bytes, $width, $height, $ext, $mime] = $this->reencode($file->getRealPath(), $type, $width, $height);
        } else {
            // Degraded mode (no GD): the file is kept as is
            $bytes = file_get_contents($file->getRealPath());
            $ext = $allowed[$type];
            $mime = image_type_to_mime_type($type);
        }

        $path = $name.'.'.$ext;
        Storage::disk(self::DISK)->put($path, $bytes);

        return Media::create([
            'path' => $path,
            'original_name' => Str::limit(basename($file->getClientOriginalName()), 180, ''),
            'mime' => $mime,
            'size' => strlen($bytes),
            'width' => $width,
            'height' => $height,
            'alt' => $alt,
        ]);
    }

    public function delete(Media $media): void
    {
        Storage::disk(self::DISK)->delete($media->path);
        $media->delete();
    }

    private function reencode(string $source, int $type, int $width, int $height): array
    {
        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_PNG => @imagecreatefrompng($source),
            IMAGETYPE_WEBP => @imagecreatefromwebp($source),
        };
        if (! $image) {
            throw new RuntimeException(__('This image could not be read.'));
        }

        if ($type === IMAGETYPE_JPEG) {
            $image = $this->applyExifOrientation($image, $source);
            [$width, $height] = [imagesx($image), imagesy($image)];
        }

        $max = config('mycms.media.max_dimension');
        if ($width > $max || $height > $max) {
            $ratio = min($max / $width, $max / $height);
            $resized = imagescale($image, (int) round($width * $ratio), (int) round($height * $ratio), IMG_BICUBIC);
            imagedestroy($image);
            $image = $resized;
            [$width, $height] = [imagesx($image), imagesy($image)];
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        ob_start();
        if (function_exists('imagewebp')) {
            imagewebp($image, null, 82);
            [$ext, $mime] = ['webp', 'image/webp'];
        } else {
            imagejpeg($image, null, 85);
            [$ext, $mime] = ['jpg', 'image/jpeg'];
        }
        $bytes = ob_get_clean();
        imagedestroy($image);

        return [$bytes, $width, $height, $ext, $mime];
    }

    private function applyExifOrientation(\GdImage $image, string $source): \GdImage
    {
        $orientation = function_exists('exif_read_data') ? (@exif_read_data($source)['Orientation'] ?? 1) : 1;

        return match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };
    }
}
