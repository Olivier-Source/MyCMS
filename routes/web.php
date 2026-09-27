<?php

use App\Http\Controllers\Site\ContactController;
use App\Http\Controllers\Site\MediaController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\SeoController;
use App\Http\Controllers\Site\ThemeAssetController;
use Illuminate\Support\Facades\Route;

// The administration is loaded first (sub-domain or /admin prefix)
require __DIR__.'/admin.php';

Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');

Route::get('/media/{path}', MediaController::class)
    ->where('path', '[a-z0-9]{40}\.(webp|jpg|png)')
    ->name('media');

// Files of the themes (styles, scripts, fonts, pictures)
Route::get('/themes/{theme}/{path}', ThemeAssetController::class)
    ->where(['theme' => '[a-z0-9][a-z0-9-]{0,49}', 'path' => '[A-Za-z0-9._\-/]+'])
    ->name('theme.asset');

Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:contact')
    ->name('contact.store');

// Pages: "/", "/about", and in the other languages "/fr", "/fr/about"
Route::get('/{path?}', [PageController::class, 'show'])
    ->where('path', '[a-z0-9]+(?:-[a-z0-9]+)*(?:/[a-z0-9]+(?:-[a-z0-9]+)*)?')
    ->name('page');
