<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'data' => 'timestamp',
    ];

    /**
     * Guarda uma mensagem que veio pelo imap no banco de dados
     */
    public static function storeNewMessage($message)
    {

        $email = new Email;

        $headers = $message->getHeader()->getAttributes();

        $header = [];
        foreach ($headers as $key => $imapHeader) {
            $header[$key] = $imapHeader->toString();
        }
        $email->header = $header;
        $email->data = $header['date'];
        $email->assunto = $headers['subject'];
        $email->remet = $headers['from'];
        $email->status = 'novo';
        $email->ano = now()->year;
        $email->save();

        // $table->string('assunto', 255);
        // $table->string('remet', 255);
        // $table->text('header');
        // $table->json('status')->nullable();
        // $table->string('unidade', 10)->nullable();
        // $table->integer('ano');
    }
}
