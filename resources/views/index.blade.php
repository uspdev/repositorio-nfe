@extends('layouts.app')

@section('content')
  <div class="card mb-4">
    <div class="card-body">
      <h1 class="h3 mb-2">
        <i class="fas fa-file-invoice text-primary mr-2"></i>{{ config('app.name', 'Repositório NF-e') }}
      </h1>
      <p class="text-muted mb-0">
        Sistema para recebimento, validação, armazenamento e consulta de Notas Fiscais Eletrônicas (NF-e)
        recebidas por e-mail, voltado para prestação de contas e conferência contábil.
      </p>

      @guest
        <hr>
        <p class="mb-3">Faça login com sua Senha Única USP para acessar as notas fiscais e ferramentas do sistema.</p>
        <a href="{{ url('login') }}" class="btn btn-primary">
          <i class="fas fa-sign-in-alt mr-1"></i> Entrar
        </a>
      @endguest
    </div>
  </div>

  @auth
    <div class="mb-3">
      <span class="text-muted">Olá, <strong>{{ auth()->user()->name }}</strong>.</span>
    </div>

    @if (!auth()->user()->can('manager') && !auth()->user()->can('admin'))
      <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        Sua conta ainda não possui permissão de acesso às funcionalidades do sistema.
        Entre em contato com um administrador para solicitar a atribuição da função adequada.
      </div>
    @else
      <div class="row">
        @can('manager')
          <div class="col-md-4 mb-3">
            <div class="card h-100">
              <div class="card-body d-flex flex-column">
                <h2 class="h5 card-title">
                  <i class="fas fa-file-alt text-primary mr-2"></i>Notas Fiscais
                </h2>
                <p class="card-text text-muted flex-grow-1">
                  Consultar NF-es armazenadas e baixar XML, DANFE em PDF e protocolo SEFAZ.
                </p>
                <a href="{{ route('nfes.index') }}" class="btn btn-outline-primary btn-sm align-self-start">
                  Acessar NF-es
                </a>
              </div>
            </div>
          </div>

          <div class="col-md-4 mb-3">
            <div class="card h-100">
              <div class="card-body d-flex flex-column">
                <h2 class="h5 card-title">
                  <i class="fas fa-server text-primary mr-2"></i>Status do Webservice
                </h2>
                <p class="card-text text-muted flex-grow-1">
                  Verificar a disponibilidade do serviço <code>nfe-ws</code> e a validade do certificado digital.
                </p>
                <a href="{{ url('nfews-status') }}" class="btn btn-outline-primary btn-sm align-self-start">
                  Ver Status
                </a>
              </div>
            </div>
          </div>
        @endcan

        @can('admin')
          <div class="col-md-4 mb-3">
            <div class="card h-100">
              <div class="card-body d-flex flex-column">
                <h2 class="h5 card-title">
                  <i class="fas fa-upload text-danger mr-2"></i>Enviar XML
                </h2>
                <p class="card-text text-muted flex-grow-1">
                  Realizar upload manual de arquivos XML de NF-e para validação e armazenamento.
                </p>
                <a href="{{ route('nfes.create') }}" class="btn btn-outline-danger btn-sm align-self-start">
                  Enviar XML
                </a>
              </div>
            </div>
          </div>

          <div class="col-md-4 mb-3">
            <div class="card h-100">
              <div class="card-body d-flex flex-column">
                <h2 class="h5 card-title">
                  <i class="fas fa-envelope text-danger mr-2"></i>E-mails
                </h2>
                <p class="card-text text-muted flex-grow-1">
                  Visualizar e-mails recebidos na caixa postal e processar anexos XML de NF-e.
                </p>
                <a href="{{ route('emails.index') }}" class="btn btn-outline-danger btn-sm align-self-start">
                  Acessar E-mails
                </a>
              </div>
            </div>
          </div>

          <div class="col-md-4 mb-3">
            <div class="card h-100">
              <div class="card-body d-flex flex-column">
                <h2 class="h5 card-title">
                  <i class="fas fa-chart-bar text-danger mr-2"></i>Estatísticas
                </h2>
                <p class="card-text text-muted flex-grow-1">
                  Consultar totais consolidados por emitente e destinatário com filtro por ano.
                </p>
                <a href="{{ url('estatisticas') }}" class="btn btn-outline-danger btn-sm align-self-start">
                  Ver Estatísticas
                </a>
              </div>
            </div>
          </div>

          <div class="col-md-4 mb-3">
            <div class="card h-100">
              <div class="card-body d-flex flex-column">
                <h2 class="h5 card-title">
                  <i class="fas fa-user-tag text-danger mr-2"></i>Funções
                </h2>
                <p class="card-text text-muted flex-grow-1">
                  Gerenciar permissões de usuários (<code>admin</code> e <code>manager</code>) no sistema.
                </p>
                <a href="{{ route('funcoes.index') }}" class="btn btn-outline-danger btn-sm align-self-start">
                  Gerenciar Funções
                </a>
              </div>
            </div>
          </div>
        @endcan
      </div>
    @endif
  @endauth
@endsection
