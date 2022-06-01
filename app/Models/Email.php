<?php

namespace App\Models;

use App\Imap\Messages;
use Webklex\IMAP\Facades\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'anexos' => [],
        'parsed' => false,
    ];

    public function status($key)
    {
        return $this->status[$key] ?? $this->defaultStatus[$key] ?? null;
    }

    public static function verificarNovos()
    {
        $lastMessage = Email::orderBy('created_at', 'desc')->first();
        $lastMessageDate = $lastMessage ? $lastMessage->created_at : now()->subDays(2);

        $messages = Messages::getMessagesSince($lastMessageDate);
        $count = 0;
        foreach ($messages as $message) {
            if (Email::storeNewMessage($message)) {
                $count++;
            }
        }
        return [
            'lastMessageDate' => $lastMessageDate,
            'totalMessages' => count($messages),
            'newMessages' => $count,
        ];
    }

    /**
     * Guarda uma mensagem que veio pelo imap no banco de dados
     */
    public static function storeNewMessage($message)
    {
        $headers = Messages::getParsedHeaders($message);

        // salva se não existir baseado na data e no assunto
        if (Email::where('date', $headers['date'])->where('subject', $headers['subject'])->doesntExist()) {
            $email = new Email;
            $email->message_id = $headers['message_id'];
            $email->date = $headers['date'];
            $email->subject = $headers['subject'];
            $email->to = $headers['to'];
            $email->from = $headers['from'];
            $email->raw_header = $message->getHeader()->raw;
            $email->raw_body = $message->getRawBody();

            $status['parsed'] = false;
            $status['anexos'] = Messages::getAttachments($message);
            // Messages::parseAttachments($message);

            $email->status = $status;
            $email->unidade = SELF::obterUnidade($email->raw_header);
            $email->save();
            return true;
        }
        return false;
    }

    /**
     * procura na string fornecida (header do email) um email correspondete à unidade
     */
    public static function obterUnidade($string)
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
            if (stripos($string, ' ' . $unidade['emailNfe']) !== false) {
                // o espaço em branco ajuda a delimitar o email
                return $unidade['sigla'];
            }
        }
        return 'NONE';
    }

    
    public function getRawBodyAttribute($value) {
        return utf8_decode($value);
    }

    /**
     * Se não converter para utf8 dá erro em alguns emails
     * 
     * Isso já acontecia no gerenciador-nfe antigo
     */
    public function setRawBodyAttribute($value) {
        $this->attributes['raw_body'] = utf8_encode($value);
    }
}
