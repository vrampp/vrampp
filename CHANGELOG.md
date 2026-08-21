# Changelog

## [0.5.0] - 2026-08-21

### Adicionado

- autenticação Basic Auth para o dashboard e a API;
- seleção de portas host livres em cada execução do Vagrant;
- propagação das portas efetivas para a página e o endpoint de status;
- base Debian Bookworm com memória reduzida e cache APT limpo.

### Segurança

- ações de serviço continuam aceitando somente POST;
- credenciais do painel permanecem no `.env` local.

## [0.4.0] - 2026-08-21

### Adicionado

- base Debian Bookworm compacta no lugar da Ubuntu genérica;
- seleção automática de portas host livres para instalações paralelas;
- painel protegido por autenticação Basic Auth configurada no `.env`;
- portas efetivas propagadas ao dashboard de cada VM.

### Decisões

- Tiny Core foi descartado nesta etapa por não oferecer a base `apt`/`systemd` necessária sem reconstruir o laboratório;
- as portas internas permanecem estáveis e somente o NAT do host varia;
- credenciais do painel são locais e nunca entram no Git.

## [0.3.0] - 2026-08-21

### Organizado

- manual principal consolidado sem capítulos duplicados ou credenciais legadas;
- `VAGRANT-CONTAINERS.md` incorporado como documentação oficial da segunda camada;
- `smoke-test.ps1` incorporado para verificação repetível do dashboard;
- `.env.example`, `LICENSE` e endpoint de serviços incluídos no release autocontido;
- contrato de caminhos estabilizado em `/vagrant/vrampp`.

### Verificação

- `bootstrap.sh` validado com `bash -n`;
- `.env` confirmado fora do índice Git;
- referências a `myXampp` e `curso-local` removidas do projeto.

## [0.2.0] - 2026-08-20

### Adicionado

- landing page técnica com Bootstrap 5 e Vue 3 carregados por CDN;
- LEDs de status para Apache/PHP, MariaDB, phpMyAdmin e FTP;
- verificação PHP do PDO e do socket local do vsftpd;
- licença MIT e contato do Prof. Rold Jr.
- `.env.example`, endpoint de serviços e smoke test operacional;
- documentação de NAT, túnel SSH, manutenção e versionamento do IaC;
- bootstrap tolerante a `.env` com CRLF do Windows;

## [0.1.0] - 2026-08-20

### Adicionado

- repositório inicial `vrampp` com branch `main`;
- `Vagrantfile` usando Ubuntu Jammy e VirtualBox;
- hostname `vrampp-local`;
- encaminhamento HTTP da VM para `localhost:55080`;
- encaminhamento FTP da VM para `localhost:55021`;
- correção automática opcional de portas com `auto_correct`;
- pasta compartilhada com todo o projeto em `/vagrant/vrampp`;
- provisionamento Bash com Apache, PHP, MariaDB, phpMyAdmin e vsftpd;
- banco `curso_exemplo` e tabela `products` em `database/init.sql`;
- registros iniciais para validar a instalação;
- página `example/index.php` com PDO e consulta `SELECT`;
- acesso local ao phpMyAdmin;
- FTP local sem acesso anônimo;
- `.gitignore` para o estado gerado em `.vagrant/`;
- `VAGRANT.md` como manual técnico de conceitos, instalação, operação, diagnóstico e caso de uso.

### Conceitos demonstrados

- box, provider, guest e host;
- portas guest e host;
- pasta compartilhada;
- provisionamento e idempotência;
- estado local do Vagrant;
- serviços Linux e dependências de uma stack LAMP;
- teste integrado de banco através de PHP, PDO e MariaDB;
- diferença entre VM tradicional e futura execução containerizada.

### Limites do marco

- ambiente destinado a desenvolvimento e laboratório local;
- credenciais do banco são didáticas e não devem ser reutilizadas;
- FTP não deve ser publicado na internet;
- não há ainda pipeline de produção nem provisionamento cloud;
- a promoção para Oracle Cloud Free Tier pertence a uma etapa posterior.