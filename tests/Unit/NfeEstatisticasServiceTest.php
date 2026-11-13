<?php

namespace Tests\Unit;

use App\Models\Nfe;
use App\Services\NfeEstatisticasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NfeEstatisticasServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_nfe_is_venda_validates_fin_nfe_and_nat_op(): void
    {
        $venda = $this->nfeComXml($this->xmlComIde('1', 'Venda de mercadoria'));
        $devolucao = $this->nfeComXml($this->xmlComIde('4', 'Devolucao de venda'));
        $remessa = $this->nfeComXml($this->xmlComIde('1', 'Remessa para conserto'));
        $invalido = $this->nfeComXml('<invalido');

        $this->assertTrue($venda->isVenda());
        $this->assertFalse($devolucao->isVenda());
        $this->assertFalse($remessa->isVenda());
        $this->assertFalse($invalido->isVenda());
    }

    private function nfeComXml(string $xml): Nfe
    {
        $nfe = new Nfe();
        $nfe->xml = $xml;

        return $nfe;
    }

    public function test_gerar_agrupa_totais_por_emitente_e_destinatario_no_periodo(): void
    {
        $this->criarNfe('1', 2024, '11111111000101', 'Emitente A', '22222222000102', 'Dest X', 100.50, '1', 'VENDA');
        $this->criarNfe('2', 2025, '11111111000101', 'Emitente A', '33333333000103', 'Dest Y', 200.00, '1', 'Venda Estadual');
        $this->criarNfe('3', 2025, '44444444000104', 'Emitente B', '22222222000102', 'Dest X', 500.00, '1', 'VENDA');
        // Fora do filtro de venda (finNFe = 4)
        $this->criarNfe('4', 2025, '44444444000104', 'Emitente B', '22222222000102', 'Dest X', 999.00, '4', 'VENDA');
        // Fora do intervalo de anos
        $this->criarNfe('5', 2019, '55555555000105', 'Emitente Antigo', '22222222000102', 'Dest X', 800.00, '1', 'VENDA');

        $service = new NfeEstatisticasService();
        $resultado = $service->gerar([2024, 2025]);

        $this->assertSame([2024, 2025], $resultado['anos']);

        $porEmit = $resultado['totaisPorEmit']->all();
        $this->assertCount(2, $porEmit);
        $this->assertSame('44444444000104', $porEmit[0]['cnpj']);
        $this->assertSame(500.0, $porEmit[0]['total']);
        $this->assertSame(1, $porEmit[0]['quantidade']);
        $this->assertSame('11111111000101', $porEmit[1]['cnpj']);
        $this->assertSame(300.5, $porEmit[1]['total']);
        $this->assertSame(2, $porEmit[1]['quantidade']);

        $porDest = $resultado['totaisPorDest']->all();
        $this->assertCount(2, $porDest);
        $this->assertSame('22222222000102', $porDest[0]['cnpj']);
        $this->assertSame(600.5, $porDest[0]['total']);
        $this->assertSame(2, $porDest[0]['quantidade']);
    }

    public function test_gerar_com_asterisco_inclui_todos_os_anos(): void
    {
        $this->criarNfe('1', 2015, '11111111000101', 'Emitente A', '22222222000102', 'Dest X', 150.00, '1', 'VENDA');
        $this->criarNfe('2', 2025, '11111111000101', 'Emitente A', '22222222000102', 'Dest X', 250.00, '1', 'VENDA');

        $service = new NfeEstatisticasService();
        $resultado = $service->gerar('*');

        $this->assertSame('*', $resultado['anos']);
        $this->assertSame(400.0, $resultado['totaisPorEmit']->first()['total']);
        $this->assertSame(2, $resultado['totaisPorEmit']->first()['quantidade']);
    }

    private function criarNfe(
        string $chave,
        int $ano,
        string $emitCnpj,
        string $emitNome,
        string $destCnpj,
        string $destNome,
        float $total,
        string $finNFe,
        string $natOp
    ): Nfe {
        $nfe = new Nfe();
        $nfe->chave = $chave;
        $nfe->ano = $ano;
        $nfe->xml = $this->xmlComIde($finNFe, $natOp);
        $nfe->prot = [];
        $nfe->emit = ['cnpj' => $emitCnpj, 'nome' => $emitNome];
        $nfe->dest = ['cnpj' => $destCnpj, 'nome' => $destNome];
        $nfe->ide = ['dataemi' => "01/01/{$ano}", 'total' => $total];
        $nfe->consulta = [];
        $nfe->infadic = '';
        $nfe->save();

        return $nfe;
    }

    private function xmlComIde(string $finNFe, string $natOp): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<nfeProc xmlns="http://www.portalfiscal.inf.br/nfe" versao="4.00">
  <NFe>
    <infNFe Id="NFe123" versao="4.00">
      <ide>
        <natOp>{$natOp}</natOp>
        <finNFe>{$finNFe}</finNFe>
      </ide>
    </infNFe>
  </NFe>
</nfeProc>
XML;
    }
}
