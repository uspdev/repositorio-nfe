@extends('layouts.app')

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Enviar XML de NF-e</h3>
    <a href="{{ route('nfes.index') }}" class="btn btn-outline-secondary btn-sm">Voltar para listagem</a>
  </div>

  <div class="card">
    <div class="card-body">
      <p class="text-muted">
        Selecione o arquivo <code>.xml</code> da NF-e para validação estrutural, consulta junto à SEFAZ e armazenamento no repositório.
      </p>

      <form action="{{ route('nfes.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="form-group">
          <label for="xml"><strong>Arquivo XML da NF-e</strong></label>
          <input
            type="file"
            class="form-control-file @error('xml') is-invalid @enderror"
            id="xml"
            name="xml"
            accept=".xml,text/xml,application/xml"
            required
          >
          @error('xml')
            <div class="invalid-feedback d-block">{{ $message }}</div>
          @enderror
          <small class="form-text text-muted">Tamanho máximo: 5 MB. Apenas arquivos com extensão <code>.xml</code>.</small>
        </div>

        <button type="submit" class="btn btn-primary">
          Enviar e Validar XML
        </button>
      </form>
    </div>
  </div>
@endsection
