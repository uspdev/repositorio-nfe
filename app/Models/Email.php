<?php

namespace App\Models;

use App\Services\Imap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Email extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'datetime',
        'status' => 'array',
    ];

    protected $defaultStatus = [
        'attachments' => [],
        'parsed' => false,
    ];

    /**
     * Retorna o status correspondente à chave $key de $email->status
     */
    public function status($key)
    {
        return $this->status[$key] ?? $this->defaultStatus[$key] ?? null;
    }

    /**
     * Retorna a data de criação do ultimo email persistido
     * 
     * Se for uma instalação nova retorna os últimos $sub dias
     */
    public static function obterDataUltimoEmail()
    {
        $sub = 5;
        $lastMessage = Email::orderBy('created_at', 'desc')->first();
        return $lastMessage ? $lastMessage->created_at : now()->subDays($sub);
    }

    /**
     * Consulta servidor imap por novos emails e guarda
     * 
     * Agendado para rodar de hora em hora no app\console\kernel.php
     */
    public static function verificarNovos()
    {
        $lastMessageDate = SELF::obterDataUltimoEmail();
        $messages = Imap::getMessagesSince($lastMessageDate);
        $count = 0;
        foreach ($messages as $message) {
            Email::guardarNovo($message) && $count++;
        }

        // vamos desconectar aqui pois email::guardarNovo tambḿe conecta no imap
        Imap::disconnect();
        return [
            'lastMessageDate' => $lastMessageDate,
            'totalMessages' => count($messages),
            'newMessages' => $count,
        ];
    }

    /**
     * Guarda uma mensagem que veio pelo imap no banco de dados se já não existir baseado na data e assunto
     * 
     * Metodo separado para poder ser chamado pelo IDLE (que não está funcionado direito)
     * 
     * @param $message Head do email parseado
     */
    public static function guardarNovo($message)
    {
        // verificando repoetidos por data e assunto
        if (Email::where('date', $message->date)->where('subject', $message->subject)->doesntExist()) {
            $email = new Email;
            $email->message_id = $message->messageId;
            $email->date = $message->date;
            $email->subject = $message->subject;
            $email->to = $message->toString;
            $email->from = mb_decode_mimeheader($message->headers->fromaddress);
            $email->raw_header = $message->headersRaw;
            $email->raw_body = Imap::connect()->getRawMail($message->id);

            $email->status = ['parsed' => false];
            $email->unidade = SELF::obterUnidade($email->raw_header);
            $email->save();
            return true;
        }
        return false;
    }

    /**
     * procura na string fornecida (header do email) um email correspondete à unidade
     */
    public static function obterUnidade($rawHeader)
    {
        $unidades = [
            [
                'id' => 1,
                'sigla' => 'EESC',
                'emailNfe' => 'nfe@eesc.usp.br',
            ],
            [
                'id' => 2,
                'sigla' => 'FO',
                'emailNfe' => 'nfe.fo@usp.br',
            ],
        ];

        foreach ($unidades as $unidade) {
            if (mb_stripos($rawHeader, ' ' . $unidade['emailNfe']) !== false) {
                // o espaço em branco ajuda a delimitar o email
                return $unidade['sigla'];
            }
        }
        return 'NONE';
    }


    /**
     * Como foi guardado em utf8, vamos reverter ao acessar
     */
    public function getRawBodyAttribute($value)
    {
        return utf8_decode($value);
    }

    /**
     * Se não converter para utf8 dá erro em alguns emails
     * 
     * Isso já acontecia no gerenciador-nfe antigo
     */
    public function setRawBodyAttribute($value)
    {
        $this->attributes['raw_body'] = utf8_encode($value);
    }
}
