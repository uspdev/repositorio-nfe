<?php

namespace App\Services;

use \Uspdev\Nfe\NfeWsConsumer;

class Nfews extends NfeWsConsumer
{
    protected static $nfeWsConsumer = null;

    public static function getInstance()
    {
        if (SELF::$nfeWsConsumer == null) {
            SELF::$nfeWsConsumer = new NfeWsConsumer(config('nfe.nfeWs.server'), config('nfe.nfeWs.user'), config('nfe.nfeWs.pwd'));
        }
        return SELF::$nfeWsConsumer;
    }
}
