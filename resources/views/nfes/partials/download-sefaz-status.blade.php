@if (isset($nfe->consulta['cStat']) && $nfe->consulta['cStat'] == 100)
  {{-- autorizado o uso --}}
  <a href="nfes/{{ $nfe->id }}/sefaz"
    title="Consultado em {{ $nfe->consulta['dhConsulta'] }}: clique para baixar/consultar novamente a SEFAZ">
    <img src="media/sefaz_file_ok.png" width="22px" height="22px">
  </a>
@elseif (isset($nfe->consulta['cStat']) && $nfe->consulta['cStat'] == 101)
  {{-- cancelamento homologado --}}
  <a href="nfes/{{ $nfe->id }}/sefaz" title="Cancelamento homologado em: {{ $nfe->consulta['dhConsulta'] }}">
    <img src="media/sefaz_file_x.png" width="22px" height="22px">
  </a>
@else
  {{-- outros --}}
  <a href="nfes/{{ $nfe->id }}/sefaz">
    <img src="media/sefaz_file_q.png" width="22px" height="22px">
  </a>
@endif

@can('admin')
  <span title="cStat">{{ isset($nfe->consulta['cStat']) ? $nfe->consulta['cStat'] : 'n/a' }}</span>
@endcan
