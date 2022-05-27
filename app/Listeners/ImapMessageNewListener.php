<?php

namespace App\Listeners;

use App\Models\Email;
use Illuminate\Support\Facades\Log;

class ImapMessageNewListener
{
    public function __construct(){
    }
    
    public function handle($event)
    {
        Log::channel('imapIdle')->notice('chegou nova mensagem',['subject'=>$event->message->subject->toString()]);
        Email::storeNewMessage($event->message);
    }
}
