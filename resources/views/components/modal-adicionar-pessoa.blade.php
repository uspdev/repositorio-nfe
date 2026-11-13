@props([
    'id' => 'modal-adicionar-pessoa',
    'title' => 'Adicionar Pessoa',
    'action',
    'submitLabel' => 'Adicionar',
])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}-label" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="{{ $id }}-label">{{ $title }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{ $action }}">
          @csrf
          <div class="mb-3">
            <select name="codpes" id="{{ $id }}-codpes" class="form-control form-control-sm" style="width: 100%;"
              required>
              <option value="">Digite o nome ou codpes..</option>
            </select>
          </div>
          <div class="text-right">
            <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-sm btn-primary">{{ $submitLabel }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@push('scripts')
  <script>
    $(document).ready(function() {
      var $modal = $('#{{ $id }}');
      var $select = $('#{{ $id }}-codpes');

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
    });
  </script>
@endpush

@once
  @push('scripts')
    <script>
      $(document).on('select2:open', function() {
        var searchField = document.querySelector('.select2-search__field');
        if (searchField) {
          searchField.focus();
        }
      });
    </script>
  @endpush
@endonce
