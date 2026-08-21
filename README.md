# vrampp

Projeto didático do Prof. Rold Jr. para aprender DevOps construindo um ambiente real, pequeno e verificável. O nome combina **Vagrant** com a ideia de um XAMPP próprio, executado em uma VM Ubuntu.

O objetivo não é entregar uma caixa-preta pronta: é acompanhar, como um projeto, a passagem da infraestrutura manual para Vagrant, depois para containers e, mais adiante, para uma esteira de publicação. Cada versão deixa uma entrega funcionando e uma decisão técnica compreensível.

O repositório funciona como laboratório independente: clone o projeto, consulte o [VAGRANT.md](VAGRANT.md), instale VirtualBox e Vagrant, copie `.env.example` para `.env` e execute `vagrant up`. A segunda camada está em [VAGRANT-CONTAINERS.md](VAGRANT-CONTAINERS.md). O Markdown é o material de apoio do projeto: explica os conceitos, descreve cada arquivo, orienta a execução e mostra como diagnosticar problemas.

O objetivo é aprender Vagrant praticando. A criação de uma stack LAMP própria é um exercício útil porque reúne vários recursos do Vagrant em um único ambiente: box, provider, VM, hostname, portas encaminhadas, pasta compartilhada, provisionamento, serviços Linux, banco de dados e ciclo de vida. O resultado é uma aplicação pequena, mas o aprendizado está em construir, verificar, parar, recriar e entender cada camada.

O `vrampp` não pretende substituir uma distribuição XAMPP pronta. Ele torna explícito como uma solução desse tipo é montada e permite observar o caminho completo entre a máquina host, a VM, o Apache, o PHP, o MariaDB e a página que consulta dados reais.

## O que esta primeira versão entrega

Esta primeira versão do `vrampp` usa somente Vagrant e uma VM Ubuntu. Ao executar o provisionamento, a VM instala e deixa disponíveis:

- Apache para servir a página;
- PHP para executar o código;
- MariaDB já instanciado com banco, tabela e registros;
- phpMyAdmin para inspeção local do banco;
- FTP local para demonstrar transferência de arquivos.

Containers ainda não fazem parte desta pasta. Eles são a próxima etapa do curso e serão usados para comparar a instalação tradicional da VM com uma infraestrutura descrita por `Dockerfile` e `compose.yaml`.

## Laboratórios

### 01. VM e stack web

Arquivos: `Vagrantfile`, `bootstrap.sh`, `database/init.sql` e `example/index.php`.

Resultado: uma VM Linux local com:

- Apache atendendo em `http://localhost:55080`;
- PHP executando a página de teste;
- MariaDB com o banco `curso_exemplo`;
- phpMyAdmin em `http://localhost:55080/phpmyadmin`;
- FTP local em `localhost:55021`.

O arquivo `VAGRANT.md` acompanha este laboratório como manual técnico de apoio. Ele contém a sequência de instalação, a explicação do `Vagrantfile`, o papel do `bootstrap.sh`, o script SQL, o teste feito por `example/index.php`, o tratamento de colisões de portas e os comandos de ciclo de vida.

### 02. vrampp v2: containers

A próxima etapa reaproveita o comportamento da stack em `Dockerfile` e `compose.yaml`, mantendo a VM tradicional como referência de comparação. O documento [VAGRANT-CONTAINERS.md](VAGRANT-CONTAINERS.md) explica IaAS, IaC, Docker Desktop, Docker Engine, volumes e healthchecks.

O estudante poderá responder a uma pergunta importante: o que mudou quando a infraestrutura deixou de ser instalada passo a passo dentro de uma VM e passou a ser declarada como serviços reproduzíveis?

### 03. Nuvem

A evolução seguinte troca a VM local pela VM Linux do Oracle Cloud Free Tier e promove a imagem publicada, sem instalar PHP, Apache ou MariaDB diretamente no servidor.

## Como executar

Pré-requisitos no Windows:

```powershell
winget install --id Oracle.VirtualBox --exact --accept-source-agreements --accept-package-agreements
winget install --id Hashicorp.Vagrant --exact --accept-source-agreements --accept-package-agreements
VBoxManage --version
vagrant --version
```

Prepare a configuracao local:

```powershell
Copy-Item .env.example .env
```

O `.env` contém o exemplo didático `root` / `vrampp` para esta VM. Ele é ignorado pelo Git e não deve ser enviado ao repositório. Em um projeto real, use secrets externos, senhas fortes e usuários com o menor privilégio necessário.

Subir o laboratório:

```powershell
vagrant validate
vagrant up
vagrant status
```

Depois de alterar `.env` ou os scripts, reaplique o provisionamento:

```powershell
vagrant provision
```

Verificar a aplicação:

```powershell
Invoke-WebRequest http://localhost:55080
```

Abrir no navegador:

- `http://localhost:55080`: página PHP com `SELECT` em `products`;
- `http://localhost:55080/phpmyadmin`: administração do banco;
- `localhost:55021`: FTP local, sem acesso anônimo.

O dashboard mostra LEDs dos serviços e permite subir, descer ou reiniciar Apache, MariaDB e FTP. Os controles são didáticos e limitados pelo wrapper sudo do provisionamento.

Desligar ou remover:

```powershell
vagrant halt
vagrant destroy
```

## Arquivos

```text
vrampp/
├── .gitignore
├── README.md
├── VAGRANT.md
├── Vagrantfile
├── bootstrap.sh
├── .env.example
├── VAGRANT-CONTAINERS.md
├── smoke-test.ps1
├── database/
│   └── init.sql
└── example/
    └── index.php
```

- `Vagrantfile`: define a box, recursos, rede, portas e provisionamento;
- `bootstrap.sh`: instala e configura a stack no guest;
- `database/init.sql`: cria banco, usuário, tabela e registros iniciais;
- `example/index.php`: landing page com Bootstrap/Vue CDN, LEDs de status, teste integrado de PHP, PDO, MariaDB e HTML;
- `example/api/services.php`: endpoint de status e ações restritas dos serviços;
- `example/api/services.php`: endpoint de status e ações restritas dos serviços;
- `LICENSE`: licença MIT do projeto, mantido pelo Prof. Rold Jr.;
- `VAGRANT.md`: manual completo de conceitos, operação, diagnóstico e caso de uso.
- `VAGRANT-CONTAINERS.md`: manual da segunda camada do curso;
- `.env.example`: modelo de configuração; o `.env` real nunca entra no Git.
- `smoke-test.ps1`: verificação HTTP do dashboard e endpoint de status.

A pasta `.vagrant/` é estado local gerado pelo Vagrant. Não deve ser criada manualmente, versionada ou enviada ao GitHub.

## Segurança

As credenciais do exemplo são somente locais e vêm de `.env`. Não versione `.env`, não reutilize `vrampp` fora da VM, não exponha MariaDB e não publique FTP sem TLS na internet. Em ambientes reais, prefira SFTP/SSH, HTTPS, secrets externos, usuários de banco com menor privilégio e uma política de firewall.

## Versão

`v0.3.0` é o marco documental e operacional atual. `v0.1.0` permanece como a primeira versão histórica da VM Vagrant.

## Licença e contato

Este projeto é distribuído sob a [licença MIT](LICENSE). Contato do Prof. Rold Jr.: `prof.roldjunior@gmail.com`.

## Referências

- Vagrant: https://developer.hashicorp.com/vagrant/docs
- VirtualBox: https://www.virtualbox.org/wiki/Documentation
- Ubuntu Server: https://documentation.ubuntu.com/server/
- Apache: https://httpd.apache.org/docs/
- PHP: https://www.php.net/docs.php
- MariaDB: https://mariadb.com/docs/
