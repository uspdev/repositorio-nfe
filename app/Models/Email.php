<?php

namespace App\Models;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Webklex\IMAP\Facades\Client;

class Email extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'header' => 'array',
        'data' => 'datetime',
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
        $client = Client::account('default');
        $client->connect();
        $folder = $client->getFolder('INBOX');
        $lastMessage = Email::orderBy('created_at', 'desc')->first();
        $lastMessageDate = $lastMessage ? $lastMessage->created_at : now()->subDays(10);

        $messages = $folder->query()->since($lastMessageDate)->softFail()->get();
        $count = 0;
        foreach ($messages as $message) {
            if (Email::storeNewMessage($message)) {
                $count++;
            }
        }
        $ret['lastMessageDate'] = $lastMessageDate;
        $ret['totalMessages'] = count($messages);
        $ret['newMessages'] = $count;

        return $ret;
    }

    /**
     * Guarda uma mensagem que veio pelo imap no banco de dados
     */
    public static function storeNewMessage($message)
    {
        $headerAttributes = $message->getHeader()->getAttributes();
        $headers = [];
        foreach ($headerAttributes as $key => $imapHeader) {
            if ($key == 'date') {
                // algumas mensagens não perseiam a data para o timezone correto usando o toString()
                $headers[$key] = $imapHeader[0]->setTimezone(config('app.timezone'));
            } else {
                $headers[$key] = $imapHeader->toString();
            }
        }

        // salva se não existir baseado na data e no assunto
        if (Email::where('data', $headerAttributes['date'])->where('assunto', $headerAttributes['subject'])->doesntExist()) {
            $email = new Email;
            $email->message_id = $headers['message_id'];
            $email->header = $headers;
            $email->data = $headers['date'];
            $email->assunto = $headerAttributes['subject'];
            $email->remet = $headerAttributes['from'];

            $status['parsed'] = false;
            $status['anexos'] = [];
            if ($message->hasAttachments()) {
                foreach ($message->getAttachments() as $attachment) {
                    $attributes = $attachment->getAttributes();
                    $anexo['content_type'] = $attributes['content_type'];
                    $anexo['name'] = $attributes['name'];
                    $anexo['size'] = $attributes['size'];
                    $status['anexos'] = $anexo;
                }
            }

            $email->status = $status;
            $email->ano = now()->year;
            $email->unidade = SELF::obterUndade(json_encode($headers));
            $email->save();
            return true;
        }
        return false;
    }

    /**
     * procura na string fornecida (header do email) um email correspondete à unidade
     */
    public static function obterUndade($string)
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
}
