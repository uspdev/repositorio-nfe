<?php

namespace App\Models;

use App\Services\Nfews;
use PhpMimeMailParser\Parser;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
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

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'prot' => 'array',
        'emit' => 'array',
        'dest' => 'array',
        'ide' => 'array',
        'consulta' => 'array',
    ];


    public static function parsearNovosEmails()
    {
        $emails = Email::where('status->parsed', false)->get();

        $countNfes = 0;
        foreach ($emails as $email) {
            $p = SELF::parsearEmail($email);
            $countNfes += $p['newNfes'];
        }
        return [
            'novosEmails' => count($emails),
            'novasNfes' => $countNfes,
        ];
    }

    /**
     * Parseia o email em busca de anexos e nfes
     * 
     * @param App\Models\Email $email
     */
    public static function parsearEmail(Email $email)
    {
        $anexos = SELF::listarAnexos($email->raw_header . $email->raw_body);
        $nfeCount = 0;
        $attachments = [];
        foreach ($anexos as $anexo) {
            $consulta = [];
            $content = $anexo->getContent();
            if (strtolower(substr($anexo->getFilename(), -3)) == 'xml') {
                $consulta = Nfews::getInstance()->consultaXML($content);
                if ($consulta['status'] == 'ok') {
                    if (Nfe::where('chave', $consulta['chave'])->doesntExist()) {
                        $nfe = new Nfe;
                        $nfe->chave = $consulta['chave'];
                        $nfe->xml = $content;
                        $nfe->prot = $consulta['prot'] ?? [];
                        $nfe->emit = $consulta['nfe']['emit'];
                        $nfe->dest = $consulta['nfe']['dest'];
                        $nfe->ide = $consulta['nfe']['ide'];
                        $nfe->consulta = $consulta['sefaz'] ?? [];
                        $nfe->infadic = $consulta['nfe']['infadic'];
                        $nfe->unidade = Email::obterUnidade($email->raw_header);
                        $nfe->ano = date_parse($consulta['nfe']['ide']['dataemi'])['year'];
                        $nfe->email_id = $email->id;
                        $nfe->save();
                        $nfeCount++;
                    }
                }
            }

            // salva no email->status anexos, nome, tamanho
            $attachment['filename'] = $anexo->getFilename();
            $attachment['size'] = strlen($content);
            $attachment['contenttype'] = $anexo->getContentType();
            $attachment['statusConsulta'] = $consulta['status'] ?? ''; // precisa melhorar isso
            $attachments[] = $attachment;
        }

        $email->status = [
            'parsed' => true,
            'parsedate' => now(),
            'attachments' => $attachments,
        ];
        $email->save();

        return [
            'newNfes' => $nfeCount,
            'attachments' => $attachments,
        ];
    }
}
