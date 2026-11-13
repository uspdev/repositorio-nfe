<?php

namespace App\Http\Controllers;

use App\Models\Email;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class EmailController extends Controller
{
    /**
     * Lista os e-mails recebidos em ordem decrescente com paginação.
     */
    public function index()
    {
        \UspTheme::activeUrl('emails');
        // não vamos selecionar todas as colunas para não sobrecarregar a memória
        $emails = Email::orderBy('id', 'desc')
            ->select(['id', 'message_id', 'date', 'subject', 'to', 'from', 'status', 'unidade'])
            ->paginate(100);

        return view('emails.index', compact('emails'));
    }

    /**
     * Verifica novos e-mails via IMAP e processa os XMLs de NF-e anexados.
     */
    public function verificarNovos()
    {
        $emailMsg = Email::verificarNovos();
        $msgEmail = 'desde: ' . $emailMsg['lastMessageDate'] . ', baixados: ' . $emailMsg['totalMessages'] . ', novos: ' . $emailMsg['newMessages'];

        $nfeMsg = Email::parsearNovos();
        $msgNfe = 'novos emails: ' . $nfeMsg['novosEmails'] . ', novas Nfes: ' . $nfeMsg['novasNfes'];

        Log::channel('imapCron')->notice('rodou manual', ['email' => $msgEmail, 'nfe' => $msgNfe]);

        Session::flash('alert-info', 'Novos emails: ' . $emailMsg['newMessages'] . ', novas NFEs: ' . $nfeMsg['novasNfes']);
        return back();
    }

    /**
     * Reprocessa os anexos de um e-mail específico em busca de NF-es.
     */
    public function parse(Email $email)
    {
        $ret = $email->parsear();

        Session::flash('alert-info', ' novas Nfes: ' . $ret['newNfes'] . ', anexos: '  . count($ret['attachments']));
        return back();
    }
}
