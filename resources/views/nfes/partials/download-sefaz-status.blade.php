@if ($nfe->consulta['cStat'] == 100)
  <a href="nfes/{{ $nfe->id }}/sefaz" title="Clique para consultar novamente a SEFAZ">
    <img src="media/sefaz_file_ok.png" width="22px" height="22px">
    {{ $nfe->consulta['dhConsulta'] }}
  </a>
@elseif ($nfe->consulta['cStat'] == 101)
  <a href="nfes/{{ $nfe->id }}/sefaz">
    <img src="media/sefaz_file_x.png" width="22px" height="22px">
  </a>
  {{ $nfe->consulta['dhConsulta'] }}
@else
  <a href="nfes/{{ $nfe->id }}/sefaz">
    <img src="media/sefaz_file_q.png" width="22px" height="22px">
  </a>
@endif
