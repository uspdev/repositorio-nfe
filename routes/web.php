<?php

use Illuminate\Support\Facades\Route;
use Webklex\IMAP\Facades\Client;

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

    $client = Client::account('default');
    $client->connect();
    $folder = $client->getFolder('INBOX');
    // $overview = $folder->overview();
    
    // dd($folder->examine());

    $messages = $folder->query()->since(now()->subDays(1))->softFail()->get();

    // $messages = $folder->messages()->all()->get();

    // foreach ($messages as $message) {
    //     echo $message->subject . $message->getAttachments()->count() . '<br><br>';
    //     // echo $message->getHeader()->get("subject").'<br>';
    //     // dd($message->getHeader());
    // }
    // dd($folder->overview());

    // dd($messages);

    // foreach ($folders as $folder) {

    //     echo $folder->name . '<br>';
    //     //Get all Messages of the current Mailbox $folder
    //     /** @var \Webklex\PHPIMAP\Support\MessageCollection $messages */
    //     $messages = $folder->messages()->since(now()->subDays(5))->get();
    //     echo count($messages);

    //     // /** @var \Webklex\PHPIMAP\Message $message */
    //     // foreach ($messages as $message) {
    //     //     echo $message->getSubject() . '<br />';
    //     //     echo 'Attachments: ' . $message->getAttachments()->count() . '<br />';
    //     //     echo $message->getHTMLBody();

    //     //     //Move the current Message to 'INBOX.read'
    //     //     if ($message->move('INBOX.read') == true) {
    //     //         echo 'Message has ben moved';
    //     //     } else {
    //     //         echo 'Message could not be moved';
    //     //     }
    //     // }
    // }


    return view('welcome', compact('messages'));
});
