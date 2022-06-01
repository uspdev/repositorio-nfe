@extends('layouts.app')

@section('content')
<div class="h3">Notas fiscais</div>

  <table class="table table-bordered table-sm table-stripped table-hover">
<tr>
    <th>Data</th>
    <th>De</th>
    <th>Assunto</th>
    <th>Unidade</th>
    <th>Anexos</th>
    <th>Parsed</th>
</tr>
    {{-- @foreach ($emails as $email)
      <tr>
        <td>{{ $email->data->format('d/m/Y H:i') }}</td>
        <td title="{{ $email->remet }}">{{ strip_tags($email->remet) }} </td>
        <td>{{ $email->assunto }} </td>
        <td>{{ $email->unidade }}</td>
        <td>{{ count($email->status('anexos')) }}</td>
        <td>{{ $email->status('parsed') ? 'sim' : 'não' }}</td>
      </tr>
    @endforeach --}}
  </table>
@endsection
