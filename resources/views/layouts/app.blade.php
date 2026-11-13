@extends('laravel-usp-theme::master')

@include('laravel-usp-theme::blocos.datatable-simples')

@section('styles')
  @parent
  @stack('styles')
@endsection

@section('javascripts_bottom')
  @parent
  @stack('scripts')
@endsection
