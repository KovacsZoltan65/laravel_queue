<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('home');
});

Route::get(
    '/upload', 
    [UploadController::class, 'index']);

Route::get(
    '/progress', 
    [UploadController::class, 'progress']
);

// Feltöltés
Route::post(
    '/upload/file', 
    [UploadController::class, 'uploadAndStore']
)->name('processFile');

Route::get(
    '/progress/data', 
    [UploadController::class, 'progressForCsvStoreProcess']
)->name('csvStoreProcess');

Route::get('import', [UploadController::class, 'show_import']);
Route::post('import', [UploadController::class, 'import'])->name('import');