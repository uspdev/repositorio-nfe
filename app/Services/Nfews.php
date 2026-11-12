<?php

namespace App\Services;

use Uspdev\Nfe\NfeWsConsumer;

class Nfews extends NfeWsConsumer
{
    protected static $nfeWsConsumer = null;

    public static function getInstance()
    {
        if (self::$nfeWsConsumer == null) {
            self::$nfeWsConsumer = new NfeWsConsumer(
                config('nfe.nfeWs.server'),
                config('nfe.nfeWs.apiKey'),
                config('nfe.nfeWs.debug', 0),
                config('nfe.nfeWs.sslVerify', true)
            );
        }
        return self::$nfeWsConsumer;
    }
}
