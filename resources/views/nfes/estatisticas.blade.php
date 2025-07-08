@extends('layouts.app')

@section('content')
  <div class="alert alert-info">
    Filtros: <br>
    &nbsp; finNfe = 1<br>
    &nbsp; natOp contém "VENDA"
  </div>

  @include('nfes.partials.filtro-anos')

  <div class="h3">Estatisticas emitente</div>

  <table class="table table-bordered table-sm table-stripped table-hover datatable-simples dt-fixed-header dt-paging-10">
    <thead>
      <tr>
        <th>CNPJ</th>
        <th>Razão social</th>
        <th>Qt.</th>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($totaisPorEmit as $empresa)
        <tr>
          <td data-order={{ $empresa['cnpj'] }}>{{ formatarCnpjCpf($empresa['cnpj']) }}</td>
          <td>{{ $empresa['nome'] }}</td>
          <td>{{ $empresa['quantidade'] }}</td>
          <td data-order={{ $empresa['total'] }}>{{ number_format($empresa['total'], 2, ',', '.') }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <hr>
  <div class="h3">Estatisticas destinatário</div>

  <table class="table table-bordered table-sm table-stripped table-hover datatable-simples dt-fixed-header">
    <thead>
      <tr>
        <th>CNPJ/CPF</th>
        <th>Nome</th>
        <th>Qt.</th>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($totaisPorDest as $empresa)
        <tr>
          <td data-order={{ $empresa['cnpj'] }}>{{ formatarCnpjCpf($empresa['cnpj']) }}</td>
          <td>{{ $empresa['nome'] }}</td>
          <td>{{ $empresa['quantidade'] }}</td>
          <td data-order={{ $empresa['total'] }}>{{ number_format($empresa['total'], 2, ',', '.') }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
@endsection
