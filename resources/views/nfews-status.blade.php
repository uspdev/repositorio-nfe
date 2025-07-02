@extends('layouts.app')

@section('content')
  Servidor: <b>{{ config('nfe.nfeWs.server') }}</b><br>
  <br>
  {!! $status !!}
@endsection
