<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LearningController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Serve files
Route::get('/posts/{filename}', function ($filename) {
    $path = storage_path('app/public/posts/' . $filename);
    if (!file_exists($path)) abort(404);
    return response()->file($path);
});

Route::get('/tenant-logos/{filename}', function ($filename) {
    $path = storage_path('app/public/tenant-logos/' . $filename);
    if (!file_exists($path)) abort(404);
    return response()->file($path);
});

Route::get('/signatures/{filename}', function ($filename) {
    $path = storage_path('app/public/signatures/' . $filename);
    if (!file_exists($path)) abort(404);
    return response()->file($path);
});

Route::get('/profile-images/{filename}', function ($filename) {
    $path = storage_path('app/public/profile-images/' . $filename);
    if (!file_exists($path)) abort(404);
    return response()->file($path);
});

Route::get('/cover-images/{filename}', function ($filename) {
    $path = storage_path('app/public/cover-images/' . $filename);
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path);
});


Route::post('/flutterwave/webhook', [WebhookController::class, 'handle'])->withoutMiddleware('csrf');
