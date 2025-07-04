<?php

namespace App\Http\Controllers;

use App\Models\Nfe;
use App\Models\Email;
use App\Imap\Messages;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\Http\Requests\StoreEmailRequest;
use App\Http\Requests\UpdateEmailRequest;

class EmailController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
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

    public function verificarNovos()
    {
        $emailMsg = Email::verificarNovos();
        $msgEmail = 'desde: ' . $emailMsg['lastMessageDate'] . ', baixados: ' . $emailMsg['totalMessages'] . ', novos: ' . $emailMsg['newMessages'];

        $nfeMsg = Nfe::parsearNovosEmails();
        $msgNfe = 'novos emails: ' . $nfeMsg['novosEmails'] . ', novas Nfes: ' . $nfeMsg['novasNfes'];

        Log::channel('imapCron')->notice('rodou manual', ['email' => $msgEmail, 'nfe' => $msgNfe]);

        Session::flash('alert-info', 'Novos emails: ' . $emailMsg['newMessages'] . ', novas NFEs: ' . $nfeMsg['novasNfes']);
        return back();
    }

    public function parse(Email $email)
    {
        $ret = Nfe::parsearEmail($email);

        Session::flash('alert-info', ' novas Nfes: ' . $ret['newNfes'] . ', anexos: '  . count($ret['attachments']));
        return back();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreEmailRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreEmailRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Email  $email
     * @return \Illuminate\Http\Response
     */
    public function show(Email $email)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Email  $email
     * @return \Illuminate\Http\Response
     */
    public function edit(Email $email)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateEmailRequest  $request
     * @param  \App\Models\Email  $email
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateEmailRequest $request, Email $email)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Email  $email
     * @return \Illuminate\Http\Response
     */
    public function destroy(Email $email)
    {
        //
    }
}
