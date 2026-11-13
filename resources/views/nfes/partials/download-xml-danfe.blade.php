<span style="white-space: nowrap;">
  <a href="nfes/{{ $nfe->id }}/xml" title="xml" class="mr-1"><img src="media/xml_file.png" width="22px" height="22px"></a>
  <a href="nfes/{{ $nfe->id }}/danfe" title="DANFE" class="mx-1"><img src="media/pdf_file.png" width="22px" height="22px"></a>
  <a href="{{ route('nfes.show', $nfe) }}" title="Ver detalhes da NF-e" class="ml-1"><img src="media/detalhes.png" width="22px" height="22px"></a>
</span>
