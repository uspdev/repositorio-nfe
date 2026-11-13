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

  <div class="modal fade" id="adicionar-gerente-modal" tabindex="-1" aria-labelledby="adicionar-gerente-label"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="adicionar-gerente-label">Adicionar Pessoa</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form method="POST" action="{{ route('funcoes.store') }}">
            @csrf
            <div class="mb-3">
              <select name="codpes" id="funcoes-codpes" class="form-control form-control-sm" style="width: 100%;" required>
                <option value="">Digite o nome ou codpes..</option>
              </select>
            </div>
            <div class="text-right">
              <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-sm btn-primary">Adicionar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('javascripts_bottom')
  @parent
  <script>
    $(document).ready(function() {
      var $modal = $('#adicionar-gerente-modal');
      var $select = $('#funcoes-codpes');

      $select.select2({
        ajax: {
          url: '{{ route('SenhaunicaFindUsers') }}',
          dataType: 'json',
          delay: 1000
        },
        dropdownParent: $modal,
        minimumInputLength: 4,
        theme: 'bootstrap4',
        width: '100%',
        language: 'pt-BR'
      });

      $modal.on('shown.bs.modal', function() {
        $select.select2('open');
      });

      $(document).on('select2:open', function() {
        var searchField = document.querySelector('.select2-search__field');
        if (searchField) {
          searchField.focus();
        }
      });
    });
  </script>
@endsection
