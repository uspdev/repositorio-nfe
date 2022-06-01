<?php

namespace App\Imap;

use Uspdev\Nfe\NfeWsConsumer;
use Webklex\IMAP\Facades\Client;

class Messages
{

    /** guarda a conexão imap para reuso */
    public static $imapClient = null;

    public static function connect() {
        if (SELF::$imapClient == null) {
            SELF::$imapClient = Client::account('default');
            SELF::$imapClient->connect();
        }
        return SELF::$imapClient;
    }

    /**
     * Retorna mensagens desde $datetime
     * 
     * @param $datetime
     * @return Collection|Array
     */
    public static function getMessagesSince($datetime)
    {
        $folder = SELF::connect()->getFolder('INBOX');
        return $folder->query()->since($datetime)->get();
    }

    // public static function getMessage()
    // {
    //     $client = Client::account('default');
    //     $client->connect();
    //     $folder = $client->getFolder('INBOX');
    //     $message = $folder->query()->all()->limit(1)->get()[0];
    //     $header = $message->getHeader()->raw;
    //     dd($header);

    //     // return $folder->query()->whereText('35220515734647000114550010000096541544477729-procNFe')->get();
    // }

    public static function getParsedHeaders($message)
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
        return $headers;
    }

    public static function getAttachments($message)
    {
        $anexos = [];
        foreach ($message->getAttachments() as $attachment) {
            $anexo = [];
            $attributes = $attachment->getAttributes();
            $anexo['content_type'] = $attributes['content_type'];
            $anexo['extension'] = substr($attributes['name'], -3);
            $anexo['name'] = $attributes['name'];
            $anexo['size'] = $attributes['size'];
            $anexos[] = $anexo;
        }
        return $anexos;
    }

    public static function parseAttachments($message)
    {
        $anexos = [];
        $nfeWsConsumer = new NfeWsConsumer(config('nfe.nfeWs.server'), config('nfe.nfeWs.user'), config('nfe.nfeWs.pwd'));

        foreach ($message->getAttachments() as $attachment) {
            $attributes = $attachment->getAttributes();

            // if ( strtolower($attachment->getExtension()) == 'xml') {
            //         $xml = $attachment->getContent();
            //         $ret = $nfeWsConsumer->consultaXML($xml);
            //     }
            // }

            $anexo = [];
            $anexo['content_type'] = $attributes['content_type'];
            $anexo['extension'] = $attachment->getExtension();
            $anexo['name'] = $attributes['name'];
            $anexo['size'] = $attributes['size'];
            $anexos[] = $anexo;
        }
        return $anexos;
    }
}
