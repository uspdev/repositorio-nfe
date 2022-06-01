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

Route::get('/', function(){
    return view('index');
});

Route::resource('nfes', NfeController::class);

Route::get('emails/verificarNovos', [EmailController::class, 'verificarNovos']);
Route::resource('emails', EmailController::class);