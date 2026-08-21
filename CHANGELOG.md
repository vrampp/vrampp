# Changelog

## [0.2.0] - 2026-08-20

### Adicionado

- landing page técnica com Bootstrap 5 e Vue 3 carregados por CDN;
- LEDs de status para Apache/PHP, MariaDB, phpMyAdmin e FTP;
- verificação PHP do PDO e do socket local do vsftpd;
- licença MIT e contato do Prof. Rold Jr.

## [0.1.0] - 2026-08-20

### Adicionado

- repositório inicial `vrampp` com branch `main`;
- `Vagrantfile` usando Ubuntu Jammy e VirtualBox;
- hostname `vrampp-local`;
- encaminhamento HTTP da VM para `localhost:55080`;
- encaminhamento FTP da VM para `localhost:55021`;
- correção automática opcional de portas com `auto_correct`;
- pasta compartilhada com todo o projeto em `/vagrant/myXampp`;
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