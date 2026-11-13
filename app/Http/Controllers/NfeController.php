<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNfeRequest;
use App\Http\Requests\UpdateNfeRequest;
use App\Models\Nfe;
use App\Services\NfeEstatisticasService;
use App\Services\Nfews;
use Illuminate\Http\Request;

class NfeController extends Controller
{
    /**
     * Lista as NF-es do ano selecionado ordenadas pela data de emissão.
     *
     * O orderByRaw assume MariaDB/MySQL (não testado em outros SGBDs).
     */
    public function index(Request $request)
    {
        \UspTheme::activeUrl('nfes');

        $anoCtl = [
            'atual' => $request->ano ?? now()->year,
            'anos' => range(now()->year, 2010),
        ];
        $nfes = Nfe::where('ano', $anoCtl['atual'])
            ->orderByRaw("STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(ide, '$.dataemi')), '%d/%m/%Y') DESC")
            // ->orderBy('ide->dataemi', 'desc')
            ->get();
        $dtSlot = view('partials.dt-slot')->with(compact('anoCtl'))->render();
        return view('nfes.index', compact('nfes', 'anoCtl', 'dtSlot'));
    }

    /**
     * Faz o download do arquivo xml da nfe
     */
    public static function xmlDownload(Nfe $nfe)
    {
        $filename = $nfe->chave . '-procNFe.xml';
        return response()->streamDownload(function () use ($nfe) {
            ob_clean();
            flush();
            echo $nfe->xml;
        }, $filename, ['content-type' => 'text/xml']);
    }

    /**
     * Faz o download da danfe
     */
    public static function danfeDownload(Nfe $nfe)
    {
        $danfe = Nfews::getInstance()->obterDanfe($nfe->xml);
        $filename = $nfe->chave . '-Danfe.pdf';
        $contentType = ['content-type' => 'application/x-pdf'];

        return response()->streamDownload(function () use ($danfe) {
            echo $danfe;
        }, $filename, $contentType);
    }

    /**
     * Faz o download do relatório da sefaz
     */
    public static function sefazDownload(Nfe $nfe)
    {
        $consulta = Nfews::getInstance()->consultaXML($nfe->xml);

        if (!isset($consulta['sefaz']['dhConsulta'])) {
            return back()->with('alert-danger', 'Consulta inválida ou não realizada: cStat = ' . $consulta['prot']['cStat']);
        }

        if ($consulta['sefaz']['dhConsulta'] != $nfe->consulta['dhConsulta']) {
            $nfe->prot = $consulta['prot'];
            $nfe->consulta = $consulta['sefaz'];
            $nfe->save();
        }

        $sefaz = Nfews::getInstance()->obterSefaz($nfe->xml);

        $filename = $nfe->chave . '-Sefaz.pdf';
        $contentType = ['content-type' => 'application/x-pdf'];

        return response()->streamDownload(function () use ($sefaz) {
            echo $sefaz;
        }, $filename, $contentType);
    }

    /**
     * Exibe o formulário para envio manual de arquivo XML de NF-e.
     */
    public function create()
    {
        \UspTheme::activeUrl('nfes');
        return view('nfes.create');
    }

    /**
     * Valida e importa o XML enviado manualmente via webservice nfe-ws.
     */
    public function store(StoreNfeRequest $request)
    {
        $content = file_get_contents($request->file('xml')->getRealPath());
        if ($content === false || trim($content) === '') {
            return back()->withInput()->with('alert-danger', 'Não foi possível ler o conteúdo do arquivo XML.');
        }

        try {
            $unidade = $request->user()?->obterSiglaUnidade();
            $res = Nfe::importarXml($content, $unidade, null);
        } catch (\Throwable $e) {
            return back()->withInput()->with('alert-danger', 'Erro ao validar XML no webservice: ' . $e->getMessage());
        }

        if ($res['status'] === 'error') {
            return back()->withInput()->with('alert-danger', $res['message']);
        }

        $alertType = $res['status'] === 'exists' ? 'alert-warning' : 'alert-success';
        return redirect()->route('nfes.show', $res['nfe'])->with($alertType, $res['message']);
    }

    /**
     * Exibe os detalhes da NF-e e a lista de produtos extraída do XML.
     */
    public function show(Nfe $nfe)
    {
        \UspTheme::activeUrl('nfes');
        return view('nfes.show', compact('nfe'));
    }

    /**
     * Atualiza a unidade associada à NF-e.
     */
    public function update(UpdateNfeRequest $request, Nfe $nfe)
    {
        $unidade = trim((string) $request->validated('unidade'));
        $nfe->unidade = $unidade !== '' ? $unidade : null;
        $nfe->save();

        return redirect()->route('nfes.show', $nfe)->with('alert-success', 'Unidade atualizada com sucesso.');
    }

    /**
     * Coleta estatísticas de emitente e destinatário para notas de venda no período.
     */
    public function estatisticas(NfeEstatisticasService $estatisticasService)
    {
        \UspTheme::activeUrl('estatisticas');

        $dados = $estatisticasService->gerar(request('anos', []));

        return view('nfes.estatisticas', $dados);
    }

    /**
     * Exibe o status do webservice nfe-ws.
     */
    public function nfewsStatus()
    {
        \UspTheme::activeUrl('nfews-status');
        try {
            $status = Nfews::getInstance()->status();
        } catch (\Throwable $e) {
            $status = ['status' => 'erro', 'erro' => $e->getMessage()];
        }

        return view('nfews-status', ['status' => $status]);
    }
}
