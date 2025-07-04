@extends('layouts.app')

@section('content')
  <div class="h3">Emails <span class="badge badge-pill badge-primary">{{ count($emails) }}</span> | <a
      href="emails/verificarNovos">Verificar novos</a></div>

  <table class="table table-bordered table-sm table-stripped table-hover datatable-simples">
    <thead>
      <tr>
        <th>Data</th>
        <th>De</th>
        <th>Para</th>
        <th>Assunto</th>
        <th>Unidade</th>
        <th>Anexos</th>
        <th>Parsed</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($emails as $email)
        <tr>
          <td>{{ $email->date->format('d/m/Y H:i') }}</td>
          <td title="{{ $email->from }}">{{ strip_tags($email->from) }} </td>
          <td title="{{ $email->to }}">{{ strip_tags($email->to) }} </td>
          <td>{{ $email->subject }} </td>
          <td>{{ $email->unidade }}</td>
          <td>
            @foreach ($email->status('attachments') as $attachment)
              {{ $attachment['filename'] }} - {{ json_encode($attachment['statusConsulta']) ?? '' }}<br>
            @endforeach
          </td>
          <td>
            <a href="emails/{{ $email->id }}/parse">P</a>
            {{ $email->status('parsed') ? 'sim' : 'não' }}
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
@endsection
