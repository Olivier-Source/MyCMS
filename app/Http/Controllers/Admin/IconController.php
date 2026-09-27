<?php

namespace App\Http\Controllers\Admin;

use App\Cms\Icons;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Tracés des icônes disponibles (sélecteur d'icônes des formulaires).
 */
class IconController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $paths = Cache::rememberForever('admin.icon-paths.'.count(Icons::all()), function () {
            $out = [];
            foreach (Icons::all() as $name) {
                preg_match_all('/\sd="([^"]+)"/', file_get_contents(Icons::path($name)), $m);
                $out[$name] = implode(' ', $m[1]);
            }

            return $out;
        });

        return response()->json($paths)->header('Cache-Control', 'private, max-age=86400');
    }
}
