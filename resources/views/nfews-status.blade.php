@extends('layouts.app')

@section('content')
  <h4>Status do Webservice NF-e</h4>
  <p>Servidor: <b>{{ config('nfe.nfeWs.server') }}</b></p>

  @if (is_array($status))
    <div class="card">
      <div class="card-header {{ ($status['status'] ?? '') === 'ok' ? 'bg-success text-white' : 'bg-danger text-white' }}">
        Certificado Digital: {{ $status['mensagem'] ?? ($status['erro'] ?? 'Status da Consulta') }}
      </div>
      <div class="card-body">
        @if (isset($status['erro']))
          <p class="text-danger mb-0"><strong>Erro:</strong> {{ $status['erro'] }}</p>
        @else
          <ul class="list-unstyled mb-0">
            <li><strong>Empresa:</strong> {{ $status['empresa'] ?? '-' }}</li>
            <li><strong>CNPJ:</strong> {{ $status['cnpj'] ?? '-' }}</li>
            <li><strong>Validade:</strong> {{ $status['validade'] ?? '-' }}</li>
            <li><strong>Situação:</strong>
              @if (!empty($status['expirado']))
                <span class="badge badge-danger">Expirado</span>
              @else
                <span class="badge badge-success">Válido</span>
              @endif
            </li>
          </ul>
        @endif
      </div>
    </div>
  @elseif ($status)
    <div class="alert alert-info">{!! $status !!}</div>
  @else
    <div class="alert alert-danger">
      Não foi possível conectar ao webservice NF-e. Verifique se o servidor está ativo e se <code>NFE_WS_SERVER</code> e <code>NFE_WS_API_KEY</code> estão configurados corretamente.
    </div>
  @endif
@endsection
