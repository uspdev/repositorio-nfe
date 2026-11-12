# Repositório NF-e

Sistema para recebimento, validação, armazenamento e consulta de Notas Fiscais Eletrônicas (NF-e) recebidas por e-mail, voltado para prestação de contas e conferência contábil no âmbito da Universidade de São Paulo (USP / EESC).

---

## Funcionalidades

* **Monitoramento Automático de Caixa Postal:** Ingestão contínua de e-mails via IMAP em busca de arquivos XML de NF-e anexados.
* **Validação e Consulta SEFAZ:** Integração com o webservice [`nfe-ws`](https://github.com/uspdev/nfe-ws) via [`uspdev/nfe-ws-consumer`](https://github.com/uspdev/nfe-ws-consumer) para validação estrutural (schema) e consulta em tempo real da situação fiscal junto à SEFAZ.
* **Repositório Centralizado:** Guarda do arquivo XML original, dados fiscais estruturados (emitente, destinatário, valores, protocolo) e histórico de consultas.
* **Emissão e Download de Documentos:** Download do arquivo XML original, DANFE em PDF e relatório de protocolo SEFAZ em PDF.
* **Autenticação Institucional:** Controle de acesso seguro via Senha Única USP ([`uspdev/senhaunica-socialite`](https://github.com/uspdev/senhaunica-socialite)) com perfis de permissão (`admin`, `manager`, `user`).
* **Interface Responsiva:** Padrão visual institucional da USP via [`uspdev/laravel-usp-theme`](https://github.com/uspdev/laravel-usp-theme).

---

## Requisitos do Sistema

* **PHP:** ^8.2 (compatível com PHP 8.2 e 8.3)
* **Extensões PHP obrigatórias:**
  * `php-mailparse` (essencial para extração dos anexos dos e-mails)
  * `php-imap` (comunicação com a caixa postal)
  * `php-xml` / `php-dom`
  * `php-curl`
  * `php-mbstring`
  * `php-sqlite3` (ambiente local) ou `php-mysql` (produção)
* **Composer:** 2.x
* **Servidor Web:** Apache com `mod_rewrite` (ou Nginx) apontando para a pasta `public/`
* **Webservice [`nfe-ws`](https://github.com/uspdev/nfe-ws):** Instância ativa do webservice e credenciais de acesso (API Key).

No Ubuntu/Debian, instale os pacotes necessários com:
```bash
sudo apt update
sudo apt install php-mailparse php-imap php-xml php-curl php-mbstring php-sqlite3
```

---

## Instalação e Configuração

### 1. Clonar o projeto e instalar dependências:
```bash
git clone https://github.com/uspdev/repositorio-nfe.git
cd repositorio-nfe
composer install
```

### 2. Configurar o ambiente:
```bash
cp .env.example .env
php artisan key:generate
```

Edite o arquivo `.env` ajustando os seguintes blocos principais:

* **URL da Aplicação:**
  ```env
  APP_URL=https://seu-servidor.usp.br/repositorio-nfe
  ```

* **Webservice `nfe-ws`:**
  ```env
  NFE_WS_SERVER=https://nfe.sua-unidade.usp.br/api
  NFE_WS_API_KEY=sua_api_key_de_64_hexadecimais
  #NFE_WS_DEBUG=0
  #NFE_WS_SSL_VERIFY=true
  ```

* **Caixa Postal IMAP:**
  ```env
  IMAP_HOST=imap.gmail.com
  IMAP_PORT=993
  IMAP_ENCRYPTION=ssl
  IMAP_PROTOCOL=imap
  IMAP_VALIDATE_CERT=true
  IMAP_USERNAME=nfe-recebidas@sua-unidade.usp.br
  IMAP_PASSWORD=sua_senha_de_aplicativo_gerada_no_google
  ```
  > **Nota sobre o Gmail:** Contas Google exigem o uso de [Senhas de Aplicativo](https://support.google.com/accounts/answer/185833?hl=pt-BR) com verificação em duas etapas ativada.

* **Autenticação Senha Única USP:**
  Cadastre a URL `https://seu_servidor/callback` no Configurador de Senha Única da USP e configure:
  ```env
  SENHAUNICA_KEY=sua_chave_oauth
  SENHAUNICA_SECRET=seu_segredo_oauth
  SENHAUNICA_CALLBACK_ID=seu_callback_id
  SENHAUNICA_ADMINS=111111,222222
  SENHAUNICA_GERENTES=333333,444444
  SENHAUNICA_CODIGO_UNIDADE=18
  ```

* **Banco de Dados:**
  Para desenvolvimento local com SQLite:
  ```env
  DB_CONNECTION=sqlite
  DB_DATABASE=storage/database.sqlite
  DB_FOREIGN_KEYS=true
  ```
  Crie o arquivo do banco se não existir:
  ```bash
  touch storage/database.sqlite
  ```

### 3. Executar as migrações:
```bash
php artisan migrate
```

---

## Rotinas em Segundo Plano

A aplicação depende da execução periódica do comando `artisan emails:receive` para buscar novos e-mails e parsear os XMLs recebidos.

### Execução Manual / Testes:
```bash
# Executa a verificação e o parsing uma única vez:
php artisan emails:receive

# Executa o agendador localmente:
php artisan schedule:work
```

### Configuração em Produção com Supervisor (Recomendado):
O [Supervisor](http://supervisord.org/) garante que o processo de monitoramento continue em execução permanente e reinicie em caso de falhas.

Crie o arquivo `/etc/supervisor/conf.d/repositorio-nfe.conf`:
```ini
[program:repositorio-nfe-imap-idle]
command=/usr/bin/php /caminho/para/repositorio-nfe/artisan emails:receive
process_name=%(program_name)s
user=www-data
numprocs=1
autostart=true
autorestart=true
startretries=3
stopsignal=QUIT
stdout_logfile=/caminho/para/repositorio-nfe/storage/logs/supervisor-imap-stdout.log
stderr_logfile=/caminho/para/repositorio-nfe/storage/logs/supervisor-imap-stderr.log
```

Atualize o Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start repositorio-nfe-imap-idle
```

### Alternativa via Cron do Sistema:
Adicione ao crontab (`crontab -e`):
```cron
* * * * * cd /caminho/para/repositorio-nfe && php artisan schedule:run >> /dev/null 2>&1
```

---

## Perfis de Acesso e Permissões

O acesso aos recursos da aplicação é controlado via *Gates* da Senha Única USP:

| Perfil | Requisito (`.env`) | Permissões no Sistema |
|---|---|---|
| **Público** | Qualquer visitante | Acesso à página inicial e direcionamento para login. |
| **Manager** | `SENHAUNICA_GERENTES` | Listagem geral de NF-e, visualização dos dados fiscais, download de XML, DANFE (PDF), relatório SEFAZ (PDF) e diagnóstico de status do `nfe-ws`. |
| **Admin** | `SENHAUNICA_ADMINS` | Todas as permissões de Manager, mais: listagem de e-mails recebidos, reprocessamento manual de mensagens (`emails/{email}/parse`), disparo síncrono de busca IMAP, métricas estatísticas e ferramentas do `laravel-tools`. |

---

## Atualização em Produção

Para aplicar atualizações do repositório em ambiente de produção:

```bash
# 1. Obter alterações do repositório
git pull

# 2. Atualizar dependências PHP sem pacotes de desenvolvimento
composer install --no-dev --optimize-autoloader

# 3. Executar migrações pendentes
php artisan migrate --force

# 4. Limpar e recriar caches do Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Reiniciar o processo no Supervisor
sudo supervisorctl restart repositorio-nfe-imap-idle
```

---

## Licença

Este projeto é distribuído sob a licença [MIT](LICENSE).
