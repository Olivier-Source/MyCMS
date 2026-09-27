<?php

namespace App\Http\Controllers\Site;

use App\Cms\MediaStore;
use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    public function __invoke(string $path): BinaryFileResponse
    {
        $media = Media::where('path', $path)->firstOrFail();
        $file = Storage::disk(MediaStore::DISK)->path($media->path);
        abort_unless(is_file($file), 404);

        return response()->file($file, [
            'Content-Type' => $media->mime,
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'",
        ]);
    }
}
