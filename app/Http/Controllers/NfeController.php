<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNfeRequest;
use App\Http\Requests\UpdateNfeRequest;
use App\Models\Nfe;

class NfeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $nfes = Nfe::all();
        return view('nfes.index', compact('nfes'));
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
