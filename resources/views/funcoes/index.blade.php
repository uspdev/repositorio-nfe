@extends('layouts.app')

@section('content')
  <div class="h3 mb-3">Funções</div>

  <div class="row">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
              Gerentes
              <button type="button" class="btn btn-sm btn-outline-primary ml-2" data-toggle="modal"
                data-target="#adicionar-gerente-modal" title="Adicionar gerente"
                @if (!function_exists('hasReplicado') || !hasReplicado()) disabled @endif>
                <i class="fas fa-plus"></i>
              </button>
            </h5>
          </div>
          <small class="text-muted d-block mt-1">
            Os gerentes podem visualizar NF-es, baixar XML/DANFE/Sefaz e consultar o status do webservice.
          </small>
        </div>
        <div class="card-body p-0">
          @if ($gerentes->isEmpty())
            <p class="text-muted p-3 mb-0">Nenhum gerente cadastrado.</p>
          @else
            <ul class="list-group list-group-flush">
              @foreach ($gerentes as $gerente)
                <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                  <span>
                    {{ $gerente->name }}
                    @if ($gerente->codpes)
                      <small class="text-muted">({{ $gerente->codpes }})</small>
                    @endif
                  </span>
                  <form method="POST" action="{{ route('funcoes.destroy', $gerente) }}"
                    onsubmit="return confirm('Remover permissão de gerente de {{ $gerente->name }}?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Remover permissão de gerente">
                      <i class="fas fa-trash-alt"></i>
                    </button>
                  </form>
                </li>
              @endforeach
            </ul>
          @endif
        </div>
      </div>
    </div>
  </div>

  <x-modal-adicionar-pessoa id="adicionar-gerente-modal" :action="route('funcoes.store')" />
@endsection
