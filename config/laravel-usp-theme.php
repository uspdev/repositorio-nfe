<?php

$menu = [
    [
        # este item de menu será substituido no momento da renderização
        'key' => 'menu_dinamico',
    ],
    [
        'text' => 'NFEs',
        'url' => 'nfes',
        'can' => 'manager',
    ],
    [
        'text' => '<span class="text-danger">Emails</span>',
        'url' => 'emails',
        'can' => 'admin',
    ],
    [
        'text' => '<span class="text-danger">Estatísticas</span>',
        'url' => 'estatisticas',
        'can' => 'admin',
    ],
    [
        'text' => 'Status',
        'url' => 'nfews-status',
        'can' => 'manager',
    ]
];

$right_menu = [
    [
        'text' => '<span class="text-danger"><i class="fas fa-user-tag"></i> Funções</span>',
        'url' => 'funcoes',
        'can' => 'admin',
    ],
    [
        'key' => 'senhaunica-socialite',
    ],
    [
        'key' => 'laravel-tools',
    ],
];


$cadastrosAuxiliaresUrl = rtrim((string) env('CADASTROS_AUXILIARES_URL', ''), '/');
$cadastrosAuxiliaresMensagensEndpoint = $cadastrosAuxiliaresUrl !== ''
    ? $cadastrosAuxiliaresUrl . '/api/mensagens'
    : '';

return [
    # valor default para a tag title, dentro da section title.
    # valor pode ser substituido pela aplicação.
    'title' => config('app.name'),

    # USP_THEME_SKIN deve ser colocado no .env da aplicação
    'skin' => env('USP_THEME_SKIN', 'uspdev'),

    # chave da sessão. Troque em caso de colisão com outra variável de sessão.
    'session_key' => 'laravel-usp-theme',

    # usado na tag base, permite usar caminhos relativos nos menus e demais elementos html
    # na versão 1 era dashboard_url
    'app_url' => config('app.url'),

    # login e logout
    'logout_method' => 'POST',
    'logout_url' => 'logout',
    'login_url' => 'login',

    # menus
    'menu' => $menu,
    'right_menu' => $right_menu,

    # mensagens flash - https://uspdev.github.io/laravel#31-mensagens-flash
    'mensagensFlash' => true,

    # integração opcional com endpoint de mensagens do cadastros-auxiliares
    'cadastros_auxiliares_mensagens_integracao' => env('CADASTROS_AUXILIARES_MENSAGENS_INTEGRACAO', false),
    'cadastros_auxiliares_mensagens_endpoint_url' => env('CADASTROS_AUXILIARES_MENSAGENS_ENDPOINT_URL', $cadastrosAuxiliaresMensagensEndpoint),
    'cadastros_auxiliares_mensagens_sistema' => env('CADASTROS_AUXILIARES_SISTEMA_NAME', ''),
    'cadastros_auxiliares_mensagens_limite' => (int) env('CADASTROS_AUXILIARES_MENSAGENS_LIMITE', 5),
    'cadastros_auxiliares_mensagens_timeout' => (int) env('CADASTROS_AUXILIARES_MENSAGENS_TIMEOUT', 5),
    'cadastros_auxiliares_mensagens_refresh' => (int) env('CADASTROS_AUXILIARES_MENSAGENS_REFRESH', 30),
    'cadastros_auxiliares_password' => env('CADASTROS_AUXILIARES_PASSWORD', ''),

    # container ou container-fluid
    'container' => 'container-fluid',

];
