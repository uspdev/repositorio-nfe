<?php

namespace App\Models;

use Uspdev\Nfe\NfeWsConsumer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Nfe extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'nfes';

    public static function parseNewEmails()
    {
        $emails = Email::where('status->parsed', false)->get();

        $nfeWsConsumer = new NfeWsConsumer(config('nfe.nfeWs.server'), config('nfe.nfeWs.user'), config('nfe.nfeWs.pwd'));
        foreach ($emails as $email) {
            $xml = ''; // pegar o anexo para verificar
            $ret = $nfeWsConsumer->consultaXML($xml);
        }
    }
}
