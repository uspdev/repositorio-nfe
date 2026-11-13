<?php

namespace App\Models;

use App\Services\Imap;
use Illuminate\Support\Carbon;
use PhpMimeMailParser\Parser;
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

    /**
     * Valores padrão para chaves ausentes em $this->status.
     *
     * @var array{attachments: array, parsed: bool}
     */
    protected $defaultStatus = [
        'attachments' => [],
        'parsed' => false,
    ];

    /**
     * Retorna o status correspondente à chave $key de $email->status.
     *
     * @param string $key Chave do array de status (ex.: 'parsed', 'attachments')
     * @return mixed
     */
    public function status($key)
    {
        return $this->status[$key] ?? $this->defaultStatus[$key] ?? null;
    }

    /**
     * Retorna a data de criação do último e-mail persistido.
     *
     * Se for uma instalação nova retorna os últimos $sub dias.
     *
     * @return \Illuminate\Support\Carbon
     */
    public static function obterDataUltimoEmail()
    {
        $sub = 5;
        $lastMessage = Email::orderBy('created_at', 'desc')->first();
        return $lastMessage ? $lastMessage->created_at : now()->subDays($sub);
    }

    /**
     * Guarda uma mensagem que veio pelo imap no banco de dados se já não existir baseado na data e assunto
     *
     * Metodo separado para poder ser chamado pelo IDLE (que não está funcionado direito)
     *
     * @param $message Head do email parseado
     * @return bool
     */
    public static function guardarNovo($message)
    {
        // Limpa a string da data, removendo qualquer "(...)" no final
        // Failed to parse time string (Thu, 9 Oct 2025 14:19:01 -0300 (-03)) at position 32
        $dateString = preg_replace('/\s*\(.*\)$/', '', $message->date ?? '');
        try {
            $parsedDate = Carbon::parse($dateString);
        } catch (\Exception $e) {
            // fallback se ainda assim falhar
            $parsedDate = now();
        }

        // verificando repetidos por data e assunto
        if (Email::where('date', $parsedDate)->where('subject', $message->subject)->doesntExist()) {
            $email = new Email;
            $email->message_id = $message->messageId;
            $email->date = $parsedDate;
            $email->subject = $message->subject;
            $email->to = $message->toString;
            $email->from = mb_decode_mimeheader($message->headers->fromaddress);
            $email->raw_header = $message->headersRaw;

            // paleativamente limitando o tamanho em 10 MB
            // if (($size = $message->getSize() / (1024 * 1024)) > 10) {
            // $email->raw_body = $message->textPlain ?? $message->textHtml;
            // } else {
            $email->raw_body = Imap::getRawMail($message->id);
            // }

            $email->status = ['parsed' => false];
            $email->unidade = SELF::obterUnidade($email->raw_header);
            $email->save();
            return true;
        }
        return false;
    }

    /**
     * Procura na string fornecida (cabeçalho do e-mail) um endereço correspondente à unidade.
     *
     * @param string|null $rawHeader Cabeçalhos brutos do e-mail
     * @return string Sigla da unidade encontrada (ex.: 'EESC', 'FO') ou 'NONE'
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
     * Processa todos os e-mails pendentes (status->parsed = false) em busca de NF-es.
     *
     * @return array{novosEmails: int, novasNfes: int} Contadores de e-mails processados e novas NF-es importadas
     */
    public static function parsearNovos(): array
    {
        $emails = self::where('status->parsed', false)->get();

        $countNfes = 0;
        foreach ($emails as $email) {
            $p = $email->parsear();
            $countNfes += $p['newNfes'];
        }

        return [
            'novosEmails' => count($emails),
            'novasNfes' => $countNfes,
        ];
    }

    /**
     * Parseia os anexos do e-mail, valida XMLs no nfe-ws e atualiza seu status.
     *
     * @return array{newNfes: int, attachments: array<int, array{filename: string, size: int, contenttype: string, statusConsulta: string}>}
     */
    public function parsear(): array
    {
        $anexos = $this->listarAnexos();
        $nfeCount = 0;
        $attachments = [];

        foreach ($anexos as $anexo) {
            $consulta = [];
            $content = $anexo->getContent();
            if (strtolower(substr($anexo->getFilename(), -3)) == 'xml') {
                $res = Nfe::importarXml($content, self::obterUnidade($this->raw_header), $this->id);
                $consulta = $res['consulta'];
                if ($res['status'] === 'created') {
                    $nfeCount++;
                }
            }

            // salva no email->status anexos, nome, tamanho
            $attachments[] = [
                'filename' => $anexo->getFilename(),
                'size' => strlen($content),
                'contenttype' => $anexo->getContentType(),
                'statusConsulta' => $consulta['status'] ?? '', // precisa melhorar isso
            ];
        }

        $this->status = [
            'parsed' => true,
            'parsedate' => now(),
            'attachments' => $attachments,
        ];
        $this->save();

        return [
            'newNfes' => $nfeCount,
            'attachments' => $attachments,
        ];
    }

    /**
     * Extrai e retorna os anexos MIME do e-mail utilizando php-mime-mail-parser.
     *
     * @return \PhpMimeMailParser\Attachment[] Lista de anexos extraídos
     */
    public function listarAnexos(): array
    {
        $parser = new Parser();
        $parser->setText($this->raw_header . $this->raw_body);

        return $parser->getAttachments([true]);
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
