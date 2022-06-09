<?php

namespace App\Http\Controllers;

use App\Models\Nfe;
use App\Services\Nfews;
use App\Http\Requests\StoreNfeRequest;
use App\Http\Requests\UpdateNfeRequest;

class NfeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $nfes = Nfe::orderBy('created_at', 'desc')->get();
        return view('nfes.index', compact('nfes'));
    }

    /**
     * Faz o download do arquivo xml da nfe
     */
    public static function xmlDownload(Nfe $nfe)
    {
        $filename = $nfe->chave . '-procNFe.xml';
        return response()->streamDownload(function () use ($nfe) {
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
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreNfeRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreNfeRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Nfe  $nfe
     * @return \Illuminate\Http\Response
     */
    public function show(Nfe $nfe)
    {
        //
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
        //
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
}
