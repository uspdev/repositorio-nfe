<?php

namespace Tests\Unit;

use App\Models\Email;
use App\Services\Imap;
use App\Services\Nfews;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PhpImap\IncomingMailHeader;
use PhpImap\Mailbox;
use Tests\TestCase;
use Uspdev\Nfe\NfeWsConsumer;

class EmailModelTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Imap::$mailbox = null;
        Nfews::setInstance(null);
        Mockery::close();
        parent::tearDown();
    }

    public function test_obter_unidade_identifies_eesc_fo_and_none(): void
    {
        $this->assertSame('EESC', Email::obterUnidade('To: nfe@eesc.usp.br'));
        $this->assertSame('FO', Email::obterUnidade('Delivered-To: nfe.fo@usp.br'));
        $this->assertSame('NONE', Email::obterUnidade('To: outro@usp.br'));
    }

    public function test_listar_anexos_extracts_attachments_from_raw_mime_email(): void
    {
        $xmlContent = '<nfeProc><NFe/></nfeProc>';
        $email = $this->createEmailWithXmlAttachment($xmlContent, 'nota-teste.xml');

        $anexos = $email->listarAnexos();

        $this->assertCount(1, $anexos);
        $this->assertSame('nota-teste.xml', $anexos[0]->getFilename());
        $this->assertSame('text/xml', $anexos[0]->getContentType());
        $this->assertSame($xmlContent, $anexos[0]->getContent());
    }

    public function test_parsear_imports_xml_attachments_and_parsear_novos_updates_status(): void
    {
        $chave = '35260500000000000100550010000000021000000020';
        $xmlContent = '<nfeProc><NFe><infNFe Id="NFe' . $chave . '"></infNFe></NFe></nfeProc>';

        $email = $this->createEmailWithXmlAttachment($xmlContent, 'nfe.xml', ['parsed' => false], 'EESC');

        $mock = Mockery::mock(NfeWsConsumer::class);
        $mock->shouldReceive('consultaXML')
            ->once()
            ->with($xmlContent)
            ->andReturn([
                'status' => 'ok',
                'chave' => $chave,
                'prot' => ['nProt' => '135260000000002'],
                'nfe' => [
                    'emit' => ['xNome' => 'Emitente Teste'],
                    'dest' => ['xNome' => 'Destinatario Teste'],
                    'ide' => ['nNF' => '2', 'serie' => '1', 'dhEmi' => '2026-05-11T09:00:00-03:00'],
                    'infadic' => 'Observacao teste',
                ],
                'sefaz' => ['cStat' => '100'],
            ]);
        Nfews::setInstance($mock);

        $resultado = Email::parsearNovos();

        $this->assertSame(1, $resultado['novosEmails']);
        $this->assertSame(1, $resultado['novasNfes']);
        $this->assertTrue((bool) ($email->fresh()->status['parsed'] ?? false));
        $this->assertDatabaseHas('nfes', [
            'chave' => $chave,
            'unidade' => 'EESC',
            'email_id' => $email->id,
        ]);
    }

    public function test_verificar_novos_fetches_imap_messages_stores_new_emails_and_skips_duplicates(): void
    {
        $rawHeader = "From: remetente@exemplo.com\r\nTo: nfe@eesc.usp.br\r\nSubject: NF-e IMAP\r\n\r\n";
        $rawMail = $rawHeader . "Corpo do email\r\n";

        $header = new IncomingMailHeader();
        $header->id = 101;
        $header->messageId = '<imap-101@exemplo.com>';
        $header->date = 'Thu, 9 Oct 2025 14:19:01 -0300 (-03)';
        $header->subject = 'NF-e IMAP';
        $header->toString = 'nfe@eesc.usp.br';
        $header->headers = (object) ['fromaddress' => 'Fornecedor <remetente@exemplo.com>'];
        $header->headersRaw = $rawHeader;

        $mailbox = Mockery::mock(Mailbox::class);
        $mailbox->shouldReceive('searchMailbox')->twice()->andReturn([101]);
        $mailbox->shouldReceive('getMailHeader')->twice()->with(101)->andReturn($header);
        $mailbox->shouldReceive('getRawMail')->once()->with(101)->andReturn($rawMail);
        $mailbox->shouldReceive('disconnect')->twice();
        Imap::$mailbox = $mailbox;

        $first = Imap::verificarNovos();

        $this->assertSame(1, $first['totalMessages']);
        $this->assertSame(1, $first['newMessages']);
        $this->assertDatabaseCount('emails', 1);

        $stored = Email::first();
        $this->assertSame('EESC', $stored->unidade);
        $this->assertFalse($stored->status('parsed'));
        $this->assertSame($rawMail, $stored->raw_body);

        Imap::$mailbox = $mailbox;
        $second = Imap::verificarNovos();

        $this->assertSame(1, $second['totalMessages']);
        $this->assertSame(0, $second['newMessages']);
        $this->assertDatabaseCount('emails', 1);
    }

    private function createEmailWithXmlAttachment(
        string $xmlContent,
        string $filename,
        array $status = ['parsed' => false],
        string $unidade = 'EESC'
    ): Email {
        $boundary = '----=_Part_123456_7890';
        $encodedXml = chunk_split(base64_encode($xmlContent));

        $rawHeader = implode("\r\n", [
            'From: remetente@exemplo.com',
            'To: nfe@eesc.usp.br',
            'Subject: Envio NF-e',
            'MIME-Version: 1.0',
            "Content-Type: multipart/mixed; boundary=\"{$boundary}\"",
        ]) . "\r\n\r\n";

        $rawBody = implode("\r\n", [
            "--{$boundary}",
            'Content-Type: text/plain; charset="utf-8"',
            'Content-Transfer-Encoding: 7bit',
            '',
            'Segue NF-e em anexo.',
            "--{$boundary}",
            "Content-Type: text/xml; name=\"{$filename}\"",
            'Content-Transfer-Encoding: base64',
            "Content-Disposition: attachment; filename=\"{$filename}\"",
            '',
            $encodedXml,
            "--{$boundary}--",
            '',
        ]);

        $email = new Email();
        $email->message_id = '<msg-' . uniqid() . '@exemplo.com>';
        $email->date = now()->toDateTimeString();
        $email->subject = 'Envio NF-e';
        $email->to = 'nfe@eesc.usp.br';
        $email->from = 'remetente@exemplo.com';
        $email->raw_header = $rawHeader;
        $email->raw_body = $rawBody;
        $email->status = $status;
        $email->unidade = $unidade;
        $email->save();

        return $email;
    }
}
