<?php

namespace Tests\Unit;

use App\Models\Nfe;
use App\Services\Nfews;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Uspdev\Nfe\NfeWsConsumer;

class NfeModelTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Nfews::setInstance(null);
        Mockery::close();
        parent::tearDown();
    }

    public function test_listar_produtos_extracts_items_from_nfe_proc_xml(): void
    {
        $nfe = new Nfe();
        $nfe->xml = $this->sampleNfeProcXml();

        $produtos = $nfe->listarProdutos();

        $this->assertCount(2, $produtos);
        $this->assertSame('1', $produtos[0]['item']);
        $this->assertSame('COD-001', $produtos[0]['codigo']);
        $this->assertSame('Notebook Dell', $produtos[0]['descricao']);
        $this->assertSame('84713012', $produtos[0]['ncm']);
        $this->assertSame('5102', $produtos[0]['cfop']);
        $this->assertSame('UN', $produtos[0]['unidade']);
        $this->assertSame(2.0, $produtos[0]['quantidade']);
        $this->assertSame(4500.0, $produtos[0]['valor_unitario']);
        $this->assertSame(9000.0, $produtos[0]['valor_total']);
        $this->assertSame('Garantia 12 meses', $produtos[0]['info_adicional']);

        $this->assertSame('2', $produtos[1]['item']);
        $this->assertSame('Mouse USB', $produtos[1]['descricao']);
        $this->assertSame('', $produtos[1]['info_adicional']);
    }

    public function test_listar_produtos_extracts_items_from_direct_nfe_xml(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<NFe xmlns="http://www.portalfiscal.inf.br/nfe">
  <infNFe Id="NFe35260500000000000100550010000000011000000010" versao="4.00">
    <det nItem="1">
      <prod>
        <cProd>P10</cProd>
        <xProd>Teclado Mecanico</xProd>
        <NCM>84716052</NCM>
        <CFOP>5102</CFOP>
        <uCom>UN</uCom>
        <qCom>1</qCom>
        <vUnCom>250.00</vUnCom>
        <vProd>250.00</vProd>
      </prod>
    </det>
  </infNFe>
</NFe>
XML;

        $nfe = new Nfe();
        $nfe->xml = $xml;
        $produtos = $nfe->listarProdutos();

        $this->assertCount(1, $produtos);
        $this->assertSame('P10', $produtos[0]['codigo']);
        $this->assertSame('Teclado Mecanico', $produtos[0]['descricao']);
    }

    public function test_listar_produtos_returns_empty_array_for_empty_or_invalid_xml(): void
    {
        $emptyNfe = new Nfe();
        $emptyNfe->xml = '';
        $this->assertSame([], $emptyNfe->listarProdutos());

        $invalidNfe = new Nfe();
        $invalidNfe->xml = '<invalid><xml>';
        $this->assertSame([], $invalidNfe->listarProdutos());
    }

    public function test_importar_xml_creates_nfe_and_detects_duplicate(): void
    {
        $chave = '35260500000000000100550010000000011000000010';
        $xml = $this->sampleNfeProcXml($chave);

        $mock = Mockery::mock(NfeWsConsumer::class);
        $mock->shouldReceive('consultaXML')
            ->twice()
            ->with($xml)
            ->andReturn($this->sampleConsultaOk($chave));
        Nfews::setInstance($mock);

        $first = Nfe::importarXml($xml, 'EESC', null);

        $this->assertSame('created', $first['status']);
        $this->assertInstanceOf(Nfe::class, $first['nfe']);
        $this->assertSame($chave, (string) $first['nfe']->chave);
        $this->assertSame('EESC', $first['nfe']->unidade);
        $this->assertNull($first['nfe']->email_id);
        $this->assertSame(2026, (int) $first['nfe']->ano);
        $this->assertDatabaseCount('nfes', 1);

        $second = Nfe::importarXml($xml, 'FO', null);

        $this->assertSame('exists', $second['status']);
        $this->assertInstanceOf(Nfe::class, $second['nfe']);
        $this->assertSame($first['nfe']->id, $second['nfe']->id);
        $this->assertSame('EESC', $second['nfe']->unidade);
        $this->assertDatabaseCount('nfes', 1);
    }

    public function test_importar_xml_returns_error_when_ws_fails(): void
    {
        $xml = '<xml>invalido</xml>';

        $mock = Mockery::mock(NfeWsConsumer::class);
        $mock->shouldReceive('consultaXML')
            ->once()
            ->with($xml)
            ->andReturn(['status' => 'erro', 'message' => 'XML malformado']);
        Nfews::setInstance($mock);

        $result = Nfe::importarXml($xml, 'EESC');

        $this->assertSame('error', $result['status']);
        $this->assertNull($result['nfe']);
        $this->assertSame('XML malformado', $result['message']);
        $this->assertDatabaseCount('nfes', 0);
    }

    public function test_atualizar_consulta_sefaz_updates_when_dh_consulta_changes(): void
    {
        $chave = '35260500000000000100550010000000011000000010';
        $nfe = new Nfe();
        $nfe->chave = $chave;
        $nfe->xml = $this->sampleNfeProcXml($chave);
        $nfe->prot = ['cStat' => '100'];
        $nfe->emit = [];
        $nfe->dest = [];
        $nfe->ide = [];
        $nfe->consulta = [];
        $nfe->infadic = '';
        $nfe->ano = 2026;
        $nfe->save();

        $mock = Mockery::mock(NfeWsConsumer::class);
        $mock->shouldReceive('consultaXML')
            ->once()
            ->with($nfe->xml)
            ->andReturn([
                'prot' => ['cStat' => '100', 'nProt' => '999'],
                'sefaz' => ['dhConsulta' => '2026-05-11T12:00:00-03:00'],
            ]);
        Nfews::setInstance($mock);

        $res = $nfe->atualizarConsultaSefaz();

        $this->assertTrue($res['ok']);
        $nfe->refresh();
        $this->assertSame('2026-05-11T12:00:00-03:00', $nfe->consulta['dhConsulta']);
        $this->assertSame('999', $nfe->prot['nProt']);
    }

    public function test_atualizar_consulta_sefaz_returns_error_when_dh_consulta_missing(): void
    {
        $nfe = new Nfe();
        $nfe->xml = '<xml/>';
        $nfe->consulta = [];

        $mock = Mockery::mock(NfeWsConsumer::class);
        $mock->shouldReceive('consultaXML')
            ->once()
            ->andReturn(['prot' => ['cStat' => '217']]);
        Nfews::setInstance($mock);

        $res = $nfe->atualizarConsultaSefaz();

        $this->assertFalse($res['ok']);
        $this->assertStringContainsString('cStat = 217', $res['message']);
    }

    private function sampleNfeProcXml(string $chave = '35260500000000000100550010000000011000000010'): string
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
        <infAdProd>Garantia 12 meses</infAdProd>
      </det>
      <det nItem="2">
        <prod>
          <cProd>COD-002</cProd>
          <xProd>Mouse USB</xProd>
          <NCM>84716053</NCM>
          <CFOP>5102</CFOP>
          <uCom>UN</uCom>
          <qCom>5.0000</qCom>
          <vUnCom>50.00</vUnCom>
          <vProd>250.00</vProd>
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
                'emit' => ['xNome' => 'Fornecedor Teste LTDA', 'CNPJ' => '00000000000100'],
                'dest' => ['xNome' => 'USP EESC', 'CNPJ' => '63025530000104'],
                'ide' => ['nNF' => '1', 'serie' => '1', 'dhEmi' => '2026-05-10T09:00:00-03:00'],
                'infadic' => 'Observacao teste',
            ],
            'sefaz' => ['cStat' => '100', 'xMotivo' => 'Autorizado o uso da NF-e'],
        ];
    }
}
