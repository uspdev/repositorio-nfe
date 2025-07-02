@extends('layouts.app')

@section('content')
  <div class="h3">Notas fiscais</div>

  <table class="table table-bordered table-sm table-stripped table-hover datatable-simples">
    <thead>
    <tr>
      <th></th>
      <th>Sefaz</th>
      <th>CNPJ Emitente</th>
      <th>Emitente</th>
      <th>Destinatário</th>
      <th>Nro/Série</th>
      <th>Data Emi</th>
      <th>Total</th>
      @can('admin')
        <th>Unidade</th>
        <th>Email</th>
      @endcan
    </tr>
    </thead>
    <tbody>
    @foreach ($nfes as $nfe)
      <tr>
        <td>
          @include('nfes.partials.download-xml-danfe')
        </td>
        <td>
          @include('nfes.partials.download-sefaz-status')
        </td>
        <td>
            {{ formatarCnpjCpf($nfe->emit['cnpj']) }}
        </td>
        <td>
          <span>({{ formatarCnpjCpf($nfe->emit['cnpj']) }}) {{ $nfe->emit['nome'] }}</span>
        </td>
        <td>
          <span title="CPF/CNPJ: {{ formatarCnpjCpf($nfe->dest['cnpj']) }}">{{ $nfe->dest['nome'] }}</span>
        </td>
        <td>{{ $nfe->ide['nro'] }}/{{ $nfe->ide['serie'] }} </td>
        <td>
            <span class="d-none">{{ formatarEndataNfe($nfe->ide['dataemi']) }}</span>
            {{ formatarDataNfe($nfe->ide['dataemi']) }}
        </td>
        <td>{{ $nfe->ide['total'] }} </td>
        @can('admin')
          <td>{{ $nfe->unidade }} </td>
          <td>{{ $nfe->email_id }} </td>
        @endcan
      </tr>
    @endforeach
    </tbody>
  </table>
@endsection
