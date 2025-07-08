<form method="GET" class="form-inline mb-3">
  <span class="mr-2">Período:</span>
  <label for="anos_0" class="mr-2">Ano inicial:</label>
  <select name="anos[]" id="anos_0" class="form-control form-control-sm mr-3">
    @for ($ano = now()->year; $ano >= 2000; $ano--)
      <option value="{{ $ano }}" {{ $ano == ($anos[0] ?? '') ? 'selected' : '' }}>
        {{ $ano }}
      </option>
    @endfor
  </select>

  <label for="anos_1" class="mr-2">Ano final:</label>
  <select name="anos[]" id="anos_1" class="form-control form-control-sm mr-3">
    @for ($ano = now()->year; $ano >= 2000; $ano--)
      <option value="{{ $ano }}" {{ $ano == ($anos[1] ?? '') ? 'selected' : '' }}>
        {{ $ano }}
      </option>
    @endfor
  </select>

  <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
</form>

@section('javascripts_bottom')
  @parent
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const anoInicial = document.getElementById('anos_0');
      const anoFinal = document.getElementById('anos_1');

      function filtrarAnosFinais() {
        const inicial = parseInt(anoInicial.value);

        Array.from(anoFinal.options).forEach(option => {
          const ano = parseInt(option.value);
          if (ano < inicial) {
            option.hidden = true;
          } else {
            option.hidden = false;
          }
        });

        // Corrige valor se ano final atual estiver fora do intervalo
        if (parseInt(anoFinal.value) < inicial) {
          // Seleciona o primeiro ano final visível
          for (const option of anoFinal.options) {
            if (!option.hidden) {
              anoFinal.value = option.value;
              break;
            }
          }
        }
      }

      anoInicial.addEventListener('change', filtrarAnosFinais);
      filtrarAnosFinais(); // Executa ao carregar
    });
  </script>
@endsection
