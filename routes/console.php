<?php

use App\Models\Email;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Artisan::command('emails:receive', function () {

    $ret = Email::verificarNovos();

    $msg = 'novas nfes desde ' . $ret['lastMessageDate'] . ' - ' . $ret['newMessages'] . ' mensagens de '. $ret['totalMessages'];
    $this->comment($msg);
    Log::channel('imapCron')->notice('rodou cron', ['mensagem' => $msg]);
})->purpose('Procura novos emails e parseia NFEs.');
