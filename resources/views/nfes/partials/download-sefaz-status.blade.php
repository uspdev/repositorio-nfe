@if ($nfe->consulta['cStat'] == 100)
  {{-- autorizado o uso --}}
  <a href="nfes/{{ $nfe->id }}/sefaz"
    title="Consultado em {{ $nfe->consulta['dhConsulta'] }}: clique para baixar/consultar novamente a SEFAZ">
    <img src="media/sefaz_file_ok.png" width="22px" height="22px">
  </a>
@elseif ($nfe->consulta['cStat'] == 101)
  {{-- cancelamento homologado --}}
  <a href="nfes/{{ $nfe->id }}/sefaz">
    <img src="media/sefaz_file_x.png" width="22px" height="22px">
  </a>
  {{ $nfe->consulta['dhConsulta'] }}
@else
  {{-- outros --}}
  <a href="nfes/{{ $nfe->id }}/sefaz">
    <img src="media/sefaz_file_q.png" width="22px" height="22px">
  </a>
@endif

@can('admin')
    <span title="cStat">{{ $nfe->consulta['cStat'] }}</span>
  {{-- @dd($nfe->consulta) --}}
@endcan
