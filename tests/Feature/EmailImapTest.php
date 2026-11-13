<?php

namespace Tests\Feature;

use App\Models\Email;
use App\Models\User;
use App\Services\Imap;
use App\Services\Nfews;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PhpImap\IncomingMailHeader;
use PhpImap\Mailbox;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Uspdev\Nfe\NfeWsConsumer;

class EmailImapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::firstOrCreate(['name' => 'admin', 'guard_name' => 'senhaunica']);
        Permission::firstOrCreate(['name' => 'manager', 'guard_name' => 'senhaunica']);
        Permission::firstOrCreate(['name' => 'boss', 'guard_name' => 'senhaunica']);
        Permission::firstOrCreate(['name' => 'user', 'guard_name' => 'senhaunica']);
    }

    protected function tearDown(): void
    {
        Imap::$mailbox = null;
        Nfews::setInstance(null);
        Mockery::close();
        parent::tearDown();
    }

    public function test_non_admin_cannot_trigger_verificar_novos(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo(Permission::findByName('manager', 'senhaunica'));

        $this->actingAs($manager)
            ->get('/emails/verificarNovos')
            ->assertForbidden();
    }

    public function test_admin_can_trigger_verificar_novos_and_import_nfe_from_imap(): void
    {
        $admin = User::factory()->create();
        $admin->givePermissionTo(Permission::findByName('admin', 'senhaunica'));

        $chave = '35260500000000000100550010000000081000000080';
        $xmlContent = '<nfeProc><NFe><infNFe Id="NFe' . $chave . '"></infNFe></NFe></nfeProc>';
        $boundary = '----=_Part_Imap_Test';
        $encodedXml = chunk_split(base64_encode($xmlContent));

        $rawHeader = implode("\r\n", [
            'From: fornecedor@exemplo.com',
            'To: nfe@eesc.usp.br',
            'Subject: NF-e via IMAP',
            'MIME-Version: 1.0',
            "Content-Type: multipart/mixed; boundary=\"{$boundary}\"",
        ]) . "\r\n\r\n";

        $rawBody = implode("\r\n", [
            "--{$boundary}",
            'Content-Type: text/plain; charset="utf-8"',
            '',
            'Segue NF-e.',
            "--{$boundary}",
            'Content-Type: text/xml; name="nfe.xml"',
            'Content-Transfer-Encoding: base64',
            'Content-Disposition: attachment; filename="nfe.xml"',
            '',
            $encodedXml,
            "--{$boundary}--",
            '',
        ]);

        $header = new IncomingMailHeader();
        $header->id = 202;
        $header->messageId = '<imap-202@exemplo.com>';
        $header->date = '2026-05-12 10:00:00';
        $header->subject = 'NF-e via IMAP';
        $header->toString = 'nfe@eesc.usp.br';
        $header->headers = (object) ['fromaddress' => 'fornecedor@exemplo.com'];
        $header->headersRaw = $rawHeader;

        $mailbox = Mockery::mock(Mailbox::class);
        $mailbox->shouldReceive('searchMailbox')->once()->andReturn([202]);
        $mailbox->shouldReceive('getMailHeader')->once()->with(202)->andReturn($header);
        $mailbox->shouldReceive('getRawMail')->once()->with(202)->andReturn($rawBody);
        $mailbox->shouldReceive('disconnect')->once();
        Imap::$mailbox = $mailbox;

        $wsMock = Mockery::mock(NfeWsConsumer::class);
        $wsMock->shouldReceive('consultaXML')
            ->once()
            ->with($xmlContent)
            ->andReturn([
                'status' => 'ok',
                'chave' => $chave,
                'prot' => ['nProt' => '135260000000008'],
                'nfe' => [
                    'emit' => ['nome' => 'Emitente IMAP'],
                    'dest' => ['nome' => 'USP EESC'],
                    'ide' => ['nNF' => '8', 'serie' => '1', 'dhEmi' => '2026-05-12T10:00:00-03:00'],
                    'infadic' => '',
                ],
                'sefaz' => ['cStat' => '100'],
            ]);
        Nfews::setInstance($wsMock);

        $response = $this->actingAs($admin)
            ->from(route('emails.index'))
            ->get('/emails/verificarNovos');

        $response->assertRedirect(route('emails.index'));
        $response->assertSessionHas('alert-info', 'Novos emails: 1, novas NFEs: 1');

        $email = Email::first();
        $this->assertNotNull($email);
        $this->assertTrue((bool) $email->status('parsed'));
        $this->assertDatabaseHas('nfes', [
            'chave' => $chave,
            'unidade' => 'EESC',
            'email_id' => $email->id,
        ]);
    }
}
