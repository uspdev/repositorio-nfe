<?php

use Webklex\IMAP\Facades\Client;
use Illuminate\Support\Facades\Route;
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

    dd(now()->format('d-m-Y H:i:s'));

    $client = Client::account('default');
    $client->connect();
    $folder = $client->getFolder('INBOX');

    $messages = $folder->query()->since(now()->subDays(1))->softFail()->get();
    return view('welcome', compact('messages'));
});

Route::get('emails/verificarNovos', [EmailController::class, 'verificarNovos']);
Route::resource('emails', EmailController::class);