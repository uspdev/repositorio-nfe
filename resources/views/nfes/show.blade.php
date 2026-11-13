@extends('layouts.app')

@section('content')
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-1">
        NF-e {{ $nfe->ide['nro'] ?? '-' }} / Série {{ $nfe->ide['serie'] ?? '-' }}
      </h3>
      <div class="text-muted small">
        <strong>Chave de Acesso:</strong> <code>{{ $nfe->chave }}</code>
      </div>
    </div>

    <div class="mt-2 mt-md-0">
      <a href="{{ url('nfes/' . $nfe->id . '/xml') }}" class="btn btn-outline-primary btn-sm">
        <img src="{{ asset('media/xml_file.png') }}" width="16" height="16" class="mr-1" alt="XML"> XML
      </a>
      <a href="{{ url('nfes/' . $nfe->id . '/danfe') }}" class="btn btn-outline-danger btn-sm">
        <img src="{{ asset('media/pdf_file.png') }}" width="16" height="16" class="mr-1" alt="DANFE"> DANFE
      </a>
      <a href="{{ url('nfes/' . $nfe->id . '/sefaz') }}" class="btn btn-outline-success btn-sm">
        SEFAZ
      </a>
      @can('admin')
        <a href="{{ route('nfes.create') }}" class="btn btn-primary btn-sm">Enviar outro XML</a>
      @endcan
      <a href="{{ route('nfes.index') }}" class="btn btn-outline-secondary btn-sm">Voltar</a>
    </div>
  </div>

  @php
    $cStat = $nfe->consulta['cStat'] ?? ($nfe->prot['cStat'] ?? null);
    $badgeClass = $cStat == 100 ? 'badge-success' : ($cStat == 101 ? 'badge-danger' : 'badge-warning');
  @endphp

  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong>Situação e Protocolo SEFAZ</strong>
      <span class="badge {{ $badgeClass }}">cStat: {{ $cStat ?? 'n/a' }}</span>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <p class="mb-1"><strong>Motivo:</strong> {{ $nfe->consulta['xMotivo'] ?? ($nfe->prot['xMotivo'] ?? '-') }}</p>
          <p class="mb-1"><strong>Protocolo:</strong> {{ $nfe->prot['nProt'] ?? ($nfe->consulta['nProt'] ?? '-') }}</p>
        </div>
        <div class="col-md-6">
          <p class="mb-1"><strong>Data/Hora Consulta SEFAZ:</strong> {{ $nfe->consulta['dhConsulta'] ?? '-' }}</p>
          <p class="mb-1"><strong>Recebimento SEFAZ:</strong> {{ $nfe->prot['dhRecbto'] ?? '-' }}</p>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header"><strong>Identificação da Nota</strong></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-3"><strong>Número:</strong> {{ $nfe->ide['nro'] ?? '-' }}</div>
        <div class="col-md-3"><strong>Série:</strong> {{ $nfe->ide['serie'] ?? '-' }}</div>
        <div class="col-md-3"><strong>Data Emissão:</strong> {{ isset($nfe->ide['dataemi']) ? formatarDataNfe($nfe->ide['dataemi']) : '-' }}</div>
        <div class="col-md-3"><strong>Valor Total:</strong> R$ {{ $nfe->ide['total'] ?? '-' }}</div>
      </div>
      @if (!empty($nfe->ide['natOp']))
        <div class="mt-2"><strong>Natureza da Operação:</strong> {{ $nfe->ide['natOp'] }}</div>
      @endif
    </div>
  </div>

  <div class="row">
    <div class="col-md-6 mb-3">
      <div class="card h-100">
        <div class="card-header"><strong>Emitente</strong></div>
        <div class="card-body">
          <p class="mb-1"><strong>Razão Social:</strong> {{ $nfe->emit['nome'] ?? '-' }}</p>
          <p class="mb-1"><strong>CNPJ/CPF:</strong> {{ !empty($nfe->emit['cnpj']) ? formatarCnpjCpf($nfe->emit['cnpj']) : '-' }}</p>
          @if (!empty($nfe->emit['ie']))
            <p class="mb-1"><strong>IE:</strong> {{ $nfe->emit['ie'] }}</p>
          @endif
        </div>
      </div>
    </div>

    <div class="col-md-6 mb-3">
      <div class="card h-100">
        <div class="card-header"><strong>Destinatário</strong></div>
        <div class="card-body">
          <p class="mb-1"><strong>Nome / Razão Social:</strong> {{ $nfe->dest['nome'] ?? '-' }}</p>
          <p class="mb-1"><strong>CNPJ/CPF:</strong> {{ !empty($nfe->dest['cnpj']) ? formatarCnpjCpf($nfe->dest['cnpj']) : '-' }}</p>
          @if (!empty($nfe->dest['ie']))
            <p class="mb-1"><strong>IE:</strong> {{ $nfe->dest['ie'] }}</p>
          @endif
        </div>
      </div>
    </div>
  </div>

  @php
    $produtos = $nfe->listarProdutos();
  @endphp

  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong>Produtos / Serviços</strong>
      <span class="badge badge-secondary">{{ count($produtos) }} {{ count($produtos) === 1 ? 'item' : 'itens' }}</span>
    </div>
    <div class="card-body p-0">
      @if (count($produtos) > 0)
        <div class="table-responsive">
          <table class="table table-sm table-striped table-hover mb-0">
            <thead class="thead-light">
              <tr>
                <th>#</th>
                <th>Código</th>
                <th>Descrição</th>
                <th>NCM</th>
                <th>CFOP</th>
                <th>Unid.</th>
                <th class="text-right">Qtd.</th>
                <th class="text-right">Vlr. Unit.</th>
                <th class="text-right">Vlr. Total</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($produtos as $prod)
                <tr>
                  <td>{{ $prod['item'] }}</td>
                  <td><code>{{ $prod['codigo'] }}</code></td>
                  <td>
                    {{ $prod['descricao'] }}
                    @if ($prod['info_adicional'] !== '')
                      <div class="text-muted small">{{ $prod['info_adicional'] }}</div>
                    @endif
                  </td>
                  <td>{{ $prod['ncm'] }}</td>
                  <td>{{ $prod['cfop'] }}</td>
                  <td>{{ $prod['unidade'] }}</td>
                  <td class="text-right">{{ number_format($prod['quantidade'], 4, ',', '.') }}</td>
                  <td class="text-right">{{ number_format($prod['valor_unitario'], 2, ',', '.') }}</td>
                  <td class="text-right"><strong>{{ number_format($prod['valor_total'], 2, ',', '.') }}</strong></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <p class="text-muted p-3 mb-0">Nenhum produto encontrado no XML desta NF-e.</p>
      @endif
    </div>
  </div>

  @if (!empty($nfe->infadic))
    <div class="card mb-3">
      <div class="card-header"><strong>Informações Adicionais</strong></div>
      <div class="card-body">
        <p class="mb-0" style="white-space: pre-line;">{{ $nfe->infadic }}</p>
      </div>
    </div>
  @endif

  <div class="card">
    <div class="card-header"><strong>Metadados no Repositório</strong></div>
    <div class="card-body">
      <div class="row align-items-center">
        <div class="col-md-4"><strong>Origem:</strong> {{ $nfe->email_id ? 'E-mail #' . $nfe->email_id : 'Envio manual' }}</div>
        <div class="col-md-4">
          @can('admin')
            <form action="{{ route('nfes.update', $nfe) }}" method="POST" class="form-inline">
              @csrf
              @method('PUT')
              <label for="unidade" class="mr-2"><strong>Unidade:</strong></label>
              <input
                type="text"
                id="unidade"
                name="unidade"
                value="{{ old('unidade', $nfe->unidade) }}"
                maxlength="10"
                placeholder="Ex: EESC"
                class="form-control form-control-sm mr-2 @error('unidade') is-invalid @enderror"
                style="width: 110px;"
              >
              <button type="submit" class="btn btn-sm btn-outline-primary">Salvar</button>
              @error('unidade')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </form>
          @else
            <strong>Unidade:</strong> {{ $nfe->unidade ?? '-' }}
          @endcan
        </div>
        <div class="col-md-4"><strong>Cadastrada em:</strong> {{ $nfe->created_at?->format('d/m/Y H:i:s') ?? '-' }}</div>
      </div>
    </div>
  </div>
@endsection
