<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NfeController;
use App\Http\Controllers\EmailController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('index');
});

Route::middleware(['auth', 'can:manager'])->group(function () {
    Route::get('/nfews-status', function () {
        \UspTheme::activeUrl('nfews-status');
        return view('nfews-status', ['status' => App\Services\Nfews::getInstance()->status()]);
    });

    Route::get('nfes/{nfe}/xml', [NfeController::class, 'xmlDownload']);
    Route::get('nfes/{nfe}/danfe', [NfeController::class, 'danfeDownload']);
    Route::get('nfes/{nfe}/sefaz', [NfeController::class, 'sefazDownload']);

    Route::resource('nfes', NfeController::class);
});

Route::middleware(['auth', 'can:admin'])->group(function () {
    Route::get('emails/verificarNovos', [EmailController::class, 'verificarNovos']);
    Route::get('emails/{email}/parse', [EmailController::class, 'parse']);
    Route::resource('emails', EmailController::class);

    Route::get('estatisticas', [NfeController::class, 'estatisticas']);
});
