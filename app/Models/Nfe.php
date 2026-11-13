<?php

namespace App\Models;

use App\Services\Nfews;
use Illuminate\Database\Eloquent\Model;
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

    /**
     * Faz o parse seguro do XML da NF-e.
     */
    private function parseXml(): ?\SimpleXMLElement
    {
        if (empty($this->xml)) {
            return null;
        }

        $prevUseErrors = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($this->xml);
        libxml_clear_errors();
        libxml_use_internal_errors($prevUseErrors);

        return $xml ?: null;
    }

    /**
     * Indica se a NF-e corresponde a uma operação de venda (finNFe = 1 e natOp contendo VENDA).
     */
    public function isVenda(): bool
    {
        $xml = $this->parseXml();
        if (!$xml || !isset($xml->NFe->infNFe->ide)) {
            return false;
        }

        $natOp = strtoupper((string) $xml->NFe->infNFe->ide->natOp);
        $finNFe = (string) $xml->NFe->infNFe->ide->finNFe;

        return $finNFe === '1' && str_contains($natOp, 'VENDA');
    }

    /**
     * Extrai a lista de produtos/serviços do XML da NF-e.
     *
     * @return array<int, array{item: string, codigo: string, descricao: string, ncm: string, cfop: string, unidade: string, quantidade: float, valor_unitario: float, valor_total: float, info_adicional: string}>
     */
    public function listarProdutos(): array
    {
        $xml = $this->parseXml();
        if (!$xml) {
            return [];
        }

        $infNFe = $xml->NFe->infNFe ?? $xml->infNFe ?? null;
        if (!$infNFe || !isset($infNFe->det)) {
            return [];
        }

        $produtos = [];
        $idx = 1;
        foreach ($infNFe->det as $det) {
            $prod = $det->prod ?? null;
            if (!$prod) {
                $idx++;
                continue;
            }

            $produtos[] = [
                'item' => (string) ($det['nItem'] ?? $idx),
                'codigo' => (string) ($prod->cProd ?? ''),
                'descricao' => (string) ($prod->xProd ?? ''),
                'ncm' => (string) ($prod->NCM ?? ''),
                'cfop' => (string) ($prod->CFOP ?? ''),
                'unidade' => (string) ($prod->uCom ?? ''),
                'quantidade' => (float) ($prod->qCom ?? 0),
                'valor_unitario' => (float) ($prod->vUnCom ?? 0),
                'valor_total' => (float) ($prod->vProd ?? 0),
                'info_adicional' => trim((string) ($det->infAdProd ?? '')),
            ];
            $idx++;
        }

        return $produtos;
    }

    /**
     * Valida e importa um XML de NF-e via webservice nfe-ws.
     *
     * @param string $content Conteúdo do arquivo XML
     * @param string|null $unidade Código da unidade (opcional)
     * @param int|null $emailId ID do e-mail de origem (opcional)
     * @return array{status: string, nfe: ?self, message: string, consulta: array}
     */
    public static function importarXml(string $content, ?string $unidade = null, ?int $emailId = null): array
    {
        $consulta = Nfews::getInstance()->consultaXML($content);

        if (!is_array($consulta)) {
            return [
                'status' => 'error',
                'nfe' => null,
                'message' => is_string($consulta) && $consulta !== ''
                    ? $consulta
                    : 'Falha ao comunicar com o webservice NF-e.',
                'consulta' => [],
            ];
        }

        if (($consulta['status'] ?? '') !== 'ok') {
            $erro = $consulta['erro'] ?? $consulta['mensagem'] ?? $consulta['message'] ?? 'XML inválido ou rejeitado pelo webservice.';
            return [
                'status' => 'error',
                'nfe' => null,
                'message' => is_string($erro) ? $erro : json_encode($erro),
                'consulta' => $consulta,
            ];
        }

        $existente = self::where('chave', $consulta['chave'])->first();
        if ($existente) {
            return [
                'status' => 'exists',
                'nfe' => $existente,
                'message' => 'Esta NF-e já estava cadastrada no repositório.',
                'consulta' => $consulta,
            ];
        }

        $nfe = new self();
        $nfe->chave = $consulta['chave'];
        $nfe->xml = $content;
        $nfe->prot = $consulta['prot'] ?? [];
        $nfe->emit = $consulta['nfe']['emit'] ?? [];
        $nfe->dest = $consulta['nfe']['dest'] ?? [];
        $nfe->ide = $consulta['nfe']['ide'] ?? [];
        $nfe->consulta = $consulta['sefaz'] ?? [];
        $nfe->infadic = $consulta['nfe']['infadic'] ?? '';
        $nfe->unidade = $unidade;
        $nfe->ano = date_parse($consulta['nfe']['ide']['dataemi'] ?? '')['year'] ?: now()->year;
        $nfe->email_id = $emailId;
        $nfe->save();

        return [
            'status' => 'created',
            'nfe' => $nfe,
            'message' => 'NF-e validada e armazenada com sucesso.',
            'consulta' => $consulta,
        ];
    }

    /**
     * Consulta o XML na Sefaz via nfe-ws e atualiza protocolo e data da consulta quando houver alteração.
     *
     * @return array{ok: bool, message: string}
     */
    public function atualizarConsultaSefaz(): array
    {
        $consulta = Nfews::getInstance()->consultaXML($this->xml);

        if (!is_array($consulta) || !isset($consulta['sefaz']['dhConsulta'])) {
            $cStat = is_array($consulta) ? ($consulta['prot']['cStat'] ?? null) : null;
            $erro = is_array($consulta)
                ? ($consulta['erro'] ?? $consulta['mensagem'] ?? $consulta['message'] ?? null)
                : (is_string($consulta) && $consulta !== '' ? $consulta : null);

            $mensagem = $cStat
                ? 'Consulta inválida ou não realizada: cStat = ' . $cStat
                : ($erro ?: 'Consulta inválida ou não realizada.');

            return ['ok' => false, 'message' => $mensagem];
        }

        if ($consulta['sefaz']['dhConsulta'] != ($this->consulta['dhConsulta'] ?? null)) {
            $this->prot = $consulta['prot'] ?? [];
            $this->consulta = $consulta['sefaz'];
            $this->save();
        }

        return ['ok' => true, 'message' => 'Consulta atualizada com sucesso.'];
    }
}
