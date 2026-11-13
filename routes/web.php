<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\FuncaoController;
use App\Http\Controllers\NfeController;

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

Route::middleware(['auth', 'can:admin'])->group(function () {
    Route::get('nfes/create', [NfeController::class, 'create'])->name('nfes.create');
    Route::post('nfes', [NfeController::class, 'store'])->name('nfes.store');

    Route::get('emails/verificarNovos', [EmailController::class, 'verificarNovos']);
    Route::get('emails/{email}/parse', [EmailController::class, 'parse']);
    Route::resource('emails', EmailController::class)->only(['index']);

    Route::get('estatisticas', [NfeController::class, 'estatisticas']);

    Route::get('funcoes', [FuncaoController::class, 'index'])->name('funcoes.index');
    Route::post('funcoes', [FuncaoController::class, 'store'])->name('funcoes.store');
    Route::delete('funcoes/{user}', [FuncaoController::class, 'destroy'])->name('funcoes.destroy');
});

Route::middleware(['auth', 'can:manager'])->group(function () {
    Route::get('/nfews-status', [NfeController::class, 'nfewsStatus']);
    Route::get('nfes/{nfe}/xml', [NfeController::class, 'xmlDownload']);
    Route::get('nfes/{nfe}/danfe', [NfeController::class, 'danfeDownload']);
    Route::get('nfes/{nfe}/sefaz', [NfeController::class, 'sefazDownload']);

    Route::resource('nfes', NfeController::class)->only(['index', 'show', 'update']);
});
