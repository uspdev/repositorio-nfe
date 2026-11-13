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

    $ret = \App\Services\Imap::verificarNovos();
    $msgEmail = 'desde: ' . $ret['lastMessageDate'] . ', baixados: ' . $ret['totalMessages'] . ', novos: ' . $ret['newMessages'];

    $ret = Email::parsearNovos();
    $msgNfe = 'novos emails: ' . $ret['novosEmails'] . ', novas Nfes: ' . $ret['novasNfes'];

    $this->comment($msgEmail);
    $this->comment($msgNfe);
    Log::channel('imapCron')->notice('rodou cron', ['email' => $msgEmail, 'nfe' => $msgNfe]);
})->purpose('Procura novos emails e parseia NFEs.');

Artisan::command('emails:prune {--days= : Dias de retenção (padrão: config imap.retention_days)} {--dry-run : Apenas exibe quantos e-mails seriam removidos}', function () {
    $daysOption = $this->option('days');
    $days = $daysOption !== null ? (int) $daysOption : (int) config('repositorio-nfe.imap.retention_days', 180);
    $dryRun = (bool) $this->option('dry-run');

    if ($days < 1) {
        $this->error('O parâmetro --days deve ser maior ou igual a 1.');
        return 1;
    }

    $total = Email::removerAntigos($days, $dryRun);

    if ($dryRun) {
        $this->info("[dry-run] {$total} e-mail(s) com mais de {$days} dia(s) seriam removidos.");
        return 0;
    }

    $this->info("{$total} e-mail(s) com mais de {$days} dia(s) removidos.");
    Log::channel('imapCron')->notice('emails:prune executado', ['days' => $days, 'removidos' => $total]);
    return 0;
})->purpose('Remove e-mails antigos conforme a política de retenção.');
