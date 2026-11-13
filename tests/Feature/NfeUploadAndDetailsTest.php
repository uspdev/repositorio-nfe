<?php

namespace Tests\Feature;

use App\Models\Nfe;
use App\Models\User;
use App\Services\Nfews;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Uspdev\Nfe\NfeWsConsumer;

class NfeUploadAndDetailsTest extends TestCase
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
        Nfews::setInstance(null);
        Mockery::close();
        parent::tearDown();
    }

    public function test_non_admin_cannot_access_create_store_or_update(): void
    {
        $manager = $this->createManagerUser();

        $nfe = $this->createStoredNfe('35260500000000000100550010000000031000000030', 'EESC');

        $this->actingAs($manager)->get(route('nfes.create'))->assertForbidden();

        $file = UploadedFile::fake()->createWithContent('nfe.xml', '<nfeProc/>');
        $this->actingAs($manager)->post(route('nfes.store'), ['xml' => $file])->assertForbidden();

        $this->actingAs($manager)->put(route('nfes.update', $nfe), ['unidade' => 'FO'])->assertForbidden();
    }

    public function test_admin_can_view_create_form(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->get(route('nfes.create'))
            ->assertOk()
            ->assertSee('Enviar XML de NF-e');
    }

    public function test_store_validates_xml_file_requirement_and_extension(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)
            ->post(route('nfes.store'), [])
            ->assertSessionHasErrors('xml');

        $invalidFile = UploadedFile::fake()->create('documento.pdf', 10, 'application/pdf');
        $this->actingAs($admin)
            ->post(route('nfes.store'), ['xml' => $invalidFile])
            ->assertSessionHasErrors('xml');
    }

    public function test_admin_can_upload_valid_xml_and_redirect_to_show(): void
    {
        $admin = $this->createAdminUser();
        $chave = '35260500000000000100550010000000041000000040';
        $xml = $this->sampleNfeProcXml($chave);

        $mock = Mockery::mock(NfeWsConsumer::class);
        $mock->shouldReceive('consultaXML')
            ->once()
            ->with($xml)
            ->andReturn($this->sampleConsultaOk($chave));
        Nfews::setInstance($mock);

        $file = UploadedFile::fake()->createWithContent('nota.xml', $xml);

        $response = $this->actingAs($admin)->post(route('nfes.store'), ['xml' => $file]);

        $nfe = Nfe::where('chave', $chave)->first();
        $this->assertNotNull($nfe);

        $response->assertRedirect(route('nfes.show', $nfe));
        $response->assertSessionHas('alert-success');
    }

    public function test_store_handles_duplicate_xml_with_warning_without_overwriting(): void
    {
        $admin = $this->createAdminUser();
        $chave = '35260500000000000100550010000000051000000050';
        $existing = $this->createStoredNfe($chave, 'EESC');
        $xml = $this->sampleNfeProcXml($chave);

        $mock = Mockery::mock(NfeWsConsumer::class);
        $mock->shouldReceive('consultaXML')
            ->once()
            ->with($xml)
            ->andReturn($this->sampleConsultaOk($chave));
        Nfews::setInstance($mock);

        $file = UploadedFile::fake()->createWithContent('nota-duplicada.xml', $xml);

        $response = $this->actingAs($admin)->post(route('nfes.store'), ['xml' => $file]);

        $response->assertRedirect(route('nfes.show', $existing));
        $response->assertSessionHas('alert-warning');
        $this->assertDatabaseCount('nfes', 1);
    }

    public function test_store_returns_error_when_webservice_rejects_xml(): void
    {
        $admin = $this->createAdminUser();
        $xml = '<xml>invalido</xml>';

        $mock = Mockery::mock(NfeWsConsumer::class);
        $mock->shouldReceive('consultaXML')
            ->once()
            ->with($xml)
            ->andReturn(['status' => 'erro', 'message' => 'Falha ao consultar XML']);
        Nfews::setInstance($mock);

        $file = UploadedFile::fake()->createWithContent('invalido.xml', $xml);

        $response = $this->actingAs($admin)
            ->from(route('nfes.create'))
            ->post(route('nfes.store'), ['xml' => $file]);

        $response->assertRedirect(route('nfes.create'));
        $response->assertSessionHas('alert-danger', 'Falha ao consultar XML');
        $this->assertDatabaseCount('nfes', 0);
    }

    public function test_manager_can_view_nfe_details_and_products(): void
    {
        $manager = $this->createManagerUser();

        $nfe = $this->createStoredNfe('35260500000000000100550010000000061000000060', 'EESC');

        $this->actingAs($manager)
            ->get(route('nfes.show', $nfe))
            ->assertOk()
            ->assertSee('Chave de Acesso:')
            ->assertSee('Notebook Dell')
            ->assertSee('Fornecedor Teste LTDA');
    }

    public function test_admin_can_update_unidade_and_validation_enforces_max_length(): void
    {
        $admin = $this->createAdminUser();
        $nfe = $this->createStoredNfe('35260500000000000100550010000000071000000070', 'EESC');

        $this->actingAs($admin)
            ->put(route('nfes.update', $nfe), ['unidade' => 'FO'])
            ->assertRedirect(route('nfes.show', $nfe))
            ->assertSessionHas('alert-success');

        $this->assertSame('FO', $nfe->fresh()->unidade);

        $this->actingAs($admin)
            ->put(route('nfes.update', $nfe), ['unidade' => 'UNIDADE-MUITO-LONGA'])
            ->assertSessionHasErrors('unidade');
    }

    private function createAdminUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findByName('admin', 'senhaunica'));

        return $user;
    }

    private function createManagerUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findByName('manager', 'senhaunica'));

        return $user;
    }

    private function createStoredNfe(string $chave, ?string $unidade = null): Nfe
    {
        $consulta = $this->sampleConsultaOk($chave);

        $nfe = new Nfe();
        $nfe->chave = $chave;
        $nfe->xml = $this->sampleNfeProcXml($chave);
        $nfe->prot = $consulta['prot'];
        $nfe->emit = $consulta['nfe']['emit'];
        $nfe->dest = $consulta['nfe']['dest'];
        $nfe->ide = $consulta['nfe']['ide'];
        $nfe->consulta = $consulta['sefaz'];
        $nfe->infadic = $consulta['nfe']['infadic'];
        $nfe->unidade = $unidade;
        $nfe->ano = 2026;
        $nfe->save();

        return $nfe;
    }

    private function sampleNfeProcXml(string $chave): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<nfeProc xmlns="http://www.portalfiscal.inf.br/nfe" versao="4.00">
  <NFe>
    <infNFe Id="NFe{$chave}" versao="4.00">
      <det nItem="1">
        <prod>
          <cProd>COD-001</cProd>
          <xProd>Notebook Dell</xProd>
          <NCM>84713012</NCM>
          <CFOP>5102</CFOP>
          <uCom>UN</uCom>
          <qCom>2.0000</qCom>
          <vUnCom>4500.00</vUnCom>
          <vProd>9000.00</vProd>
        </prod>
      </det>
    </infNFe>
  </NFe>
</nfeProc>
XML;
    }

    private function sampleConsultaOk(string $chave): array
    {
        return [
            'status' => 'ok',
            'chave' => $chave,
            'prot' => ['nProt' => '135260000000001', 'dhRecbto' => '2026-05-10T10:00:00-03:00'],
            'nfe' => [
                'emit' => ['nome' => 'Fornecedor Teste LTDA', 'cnpj' => '00000000000100'],
                'dest' => ['nome' => 'USP EESC', 'cnpj' => '63025530000104'],
                'ide' => ['nNF' => '1', 'serie' => '1', 'dhEmi' => '2026-05-10T09:00:00-03:00'],
                'infadic' => 'Observacao teste',
            ],
            'sefaz' => ['cStat' => '100', 'xMotivo' => 'Autorizado o uso da NF-e'],
        ];
    }
}
