@extends('layouts.app')

@section('content')
<div class="h3">Notas fiscais</div>

  <table class="table table-bordered table-sm table-stripped table-hover">
<tr>
  <th>Sefaz</th>
  <th>Emitente</th>
    <th>Destinatário</th>
    <th>Nro/Série</th>
    <th>Data Emi</th>
    <th>Total</th>
    <th>Unidade</th>
    <th>Email</th>
</tr>
    @foreach ($nfes as $nfe)
      <tr>
        <td>
          {{ $nfe->consulta['cStat'] }} |
          <a href="nfes/{{ $nfe->id }}/xml" title="xml"><i class="fas fa-file-code"></i></a> |
          <a href="nfes/{{ $nfe->id }}/danfe" title="DANFE"><i class="far fa-file-pdf"></i></a> |
          <a href="nfes/{{ $nfe->id }}/sefaz" title="Sefaz"><i class="fa fa-file-prescription"></i></a>
        </td>
        <td title="{{ formatarCnpjCpf($nfe->emit['cnpj']) }}">{{ $nfe->emit['nome'] }}</td>
        <td>{{ $nfe->dest['nome'] }} - {{ formatarCnpjCpf($nfe->dest['cnpj']) }}</td>
        <td>{{ $nfe->ide['nro'] }}/{{ $nfe->ide['serie'] }} </td>
        <td>{{ formatarDataNfe($nfe->ide['dataemi']) }} </td>
        <td>{{ $nfe->ide['total'] }} </td>
        <td>{{ $nfe->unidade }} </td>
        <td>{{ $nfe->email_id }} </td>
      </tr>
    @endforeach
  </table>
@endsection
