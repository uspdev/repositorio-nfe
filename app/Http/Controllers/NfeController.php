<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNfeRequest;
use App\Http\Requests\UpdateNfeRequest;
use App\Models\Nfe;
use App\Services\Nfews;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NfeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * O orderby RAW implica em mariadb. não testado em mysql ou outro
     *
     * @return \Illuminate\Http\Response
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
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        \UspTheme::activeUrl('nfes');
        return view('nfes.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreNfeRequest  $request
     * @return \Illuminate\Http\Response
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
     * Display the specified resource.
     *
     * @param  \App\Models\Nfe  $nfe
     * @return \Illuminate\Http\Response
     */
    public function show(Nfe $nfe)
    {
        \UspTheme::activeUrl('nfes');
        return view('nfes.show', compact('nfe'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Nfe  $nfe
     * @return \Illuminate\Http\Response
     */
    public function edit(Nfe $nfe)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateNfeRequest  $request
     * @param  \App\Models\Nfe  $nfe
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateNfeRequest $request, Nfe $nfe)
    {
        $unidade = trim((string) $request->validated('unidade'));
        $nfe->unidade = $unidade !== '' ? $unidade : null;
        $nfe->save();

        return redirect()->route('nfes.show', $nfe)->with('alert-success', 'Unidade atualizada com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Nfe  $nfe
     * @return \Illuminate\Http\Response
     */
    public function destroy(Nfe $nfe)
    {
        //
    }

    /**
     * Coleta estatísticas de destinatátio e remtente e mostra em listas
     */
    public function estatisticas()
    {
        \UspTheme::activeUrl('estatisticas');

        $anos = request('anos', []);
        if (!isset($anos[0])) {
            $anos[0] = now()->year - 5; // ano inicial;
        }
        if (!isset($anos[1]) || $anos[1] < $anos[0]) {
            $anos[1] = now()->year; // ano final
        }
        // $anos = '*';

        $nfes = Nfe::when($anos === '*', function ($query) {
            // pega todos
            return $query;
        })
            ->when(is_array($anos), function ($query) use ($anos) {
                // pega intervalo de anos
                return $query->whereBetween('ano', [$anos[0], $anos[1]]);
            })
            ->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(ide, '$.dataemi')) DESC")
            ->get();

        $totaisPorEmit = $nfes
            ->filter(function ($nfe) {
                $xml = simplexml_load_string($nfe->xml);
                if (!$xml || !isset($xml->NFe->infNFe->ide)) {
                    return false;
                }

                $natOp  = strtoupper((string) $xml->NFe->infNFe->ide->natOp);
                $finNFe = (string) $xml->NFe->infNFe->ide->finNFe;

                return $finNFe === '1' && str_contains($natOp, 'VENDA');
            })
            ->groupBy('emit.cnpj')
            ->map(function ($grupo, $cnpj) {
                $nome = collect($grupo)->pluck('emit.nome')->first() ?? '(sem nome)';
                return [
                    'cnpj' => $cnpj,
                    'nome' => $nome,
                    'total' => $grupo->sum(fn($nfe) => (float) ($nfe->ide['total'] ?? 0)),
                    'quantidade' => $grupo->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();

        $totaisPorDest = $nfes
            ->filter(function ($nfe) {
                $xml = simplexml_load_string($nfe->xml);
                if (!$xml || !isset($xml->NFe->infNFe->ide)) {
                    return false;
                }

                $natOp  = strtoupper((string) $xml->NFe->infNFe->ide->natOp);
                $finNFe = (string) $xml->NFe->infNFe->ide->finNFe;

                return $finNFe === '1' && str_contains($natOp, 'VENDA');
            })
            ->groupBy('dest.cnpj') // agrupando por destinatário
            ->map(function ($grupo, $cnpj) {
                $nome = collect($grupo)->pluck('dest.nome')->first() ?? '(sem nome)';
                return [
                    'cnpj'       => $cnpj,
                    'nome'       => $nome,
                    'total'      => $grupo->sum(fn($nfe) => (float) ($nfe->ide['total'] ?? 0)),
                    'quantidade' => $grupo->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();

        return view('nfes.estatisticas', compact('totaisPorEmit', 'totaisPorDest', 'anos'));
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
