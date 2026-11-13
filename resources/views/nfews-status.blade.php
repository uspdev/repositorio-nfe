@extends('layouts.app')

@section('content')
  <h4>Status do Webservice NF-e</h4>

  @if (is_array($status))
    @php
      $desatualizado = !empty($status['modo_compatibilidade'])
        || (!empty($status['versao_em_uso']) && !empty($status['versao_corrente']) && $status['versao_em_uso'] !== $status['versao_corrente']);
      $baseServer = preg_replace('#/v\d+$#', '', rtrim((string) config('repositorio-nfe.nfeWs.server'), '/'));
    @endphp

    <div class="row">
      <div class="col-md-6 mb-3">
        <div class="card h-100">
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
      </div>

      <div class="col-md-6 mb-3">
        <div class="card h-100">
          <div class="card-header {{ $desatualizado ? 'bg-warning' : 'bg-light' }}">
            Informações da API
          </div>
          <div class="card-body">
            <ul class="list-unstyled mb-0">
              <li><strong>Servidor:</strong> <code>{{ config('repositorio-nfe.nfeWs.server') }}</code></li>
              <li>
                <strong>Versão em uso:</strong>
                @if (!empty($status['versao_em_uso']))
                  @if ($desatualizado)
                    <span class="badge badge-warning">{{ $status['versao_em_uso'] }}</span>
                  @else
                    {{ $status['versao_em_uso'] }}
                  @endif
                @else
                  -
                @endif
              </li>
              <li><strong>Versão mais recente:</strong> {{ $status['versao_corrente'] ?? '-' }}</li>
            </ul>

            @if ($desatualizado)
              <div class="alert alert-warning mt-3 mb-0">
                <strong>Atenção:</strong> Configure <code>NFE_WS_SERVER</code> (<code>$srv</code> do consumer) para apontar para a versão mais recente:
                <code>{{ $baseServer }}/{{ $status['versao_corrente'] ?? 'v1' }}</code>.
              </div>
            @endif
          </div>
        </div>
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
