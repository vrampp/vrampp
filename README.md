# vrampp

O `vrampp` é uma ferramenta de infraestrutura local que entrega uma VM Debian compacta com Apache, PHP, MariaDB, phpMyAdmin e FTP. O projeto nasceu de uma iniciativa acadêmica do Prof. Rold Jr., mas seu contrato atual é operacional: fornecer um ambiente reproduzível para aplicações que precisam de uma stack web LAMP local.

O objetivo é fornecer uma base explícita, versionada e verificável. A infraestrutura é descrita por arquivos, provisionada por Vagrant e operada por comandos previsíveis. A camada posterior de containers é documentada separadamente para permitir uma migração controlada.

O repositório funciona como laboratório independente: clone o projeto, consulte o [VAGRANT.md](VAGRANT.md), instale VirtualBox e Vagrant, copie `.env.example` para `.env` e execute `vagrant up`. A segunda camada está em [VAGRANT-CONTAINERS.md](VAGRANT-CONTAINERS.md). O Markdown é o material de apoio do projeto: explica os conceitos, descreve cada arquivo, orienta a execução e mostra como diagnosticar problemas.

O produto reúne box, provider, VM, hostname, portas encaminhadas, pasta compartilhada, provisionamento, serviços Linux, banco de dados e ciclo de vida em uma unidade versionada. A operação inclui criação, verificação, manutenção, parada, atualização e reconstrução controlada.

O `vrampp` não pretende substituir uma distribuição XAMPP pronta. Ele torna explícito como uma solução desse tipo é montada e permite observar o caminho completo entre a máquina host, a VM, o Apache, o PHP, o MariaDB e a página que consulta dados reais.

## Classificação de infraestrutura

Em termos de DevOps, o `vrampp` ocupa estas camadas:

| Conceito | Papel do vrampp |
| --- | --- |
| IaC | `Vagrantfile`, `bootstrap.sh`, `.env.example` e schema versionam o estado desejado e o provisionamento. |
| IaaS local | VirtualBox fornece a capacidade computacional local; o vrampp empacota uma oferta padronizada sobre ela. |
| Plataforma local | Apache, PHP, MariaDB, phpMyAdmin e FTP formam os serviços consumidos pela aplicação. |
| Ferramenta de infraestrutura | O repositório oferece comandos, contratos de portas, configuração e smoke test para operar a stack. |

Tecnicamente, o VirtualBox é o provider de virtualização e o Vagrant é o orquestrador de ciclo de vida. O `vrampp` é o produto que combina esses recursos em uma infraestrutura local reutilizável. Não é cloud IaaS pública; é uma camada local com comportamento semelhante para desenvolvimento e integração.

## O que esta primeira versão entrega

Esta primeira versão do `vrampp` usa somente Vagrant e uma VM Debian Bookworm compacta. Ao executar o provisionamento, a VM instala e deixa disponíveis:

- Debian Bookworm como base enxuta e compatível com `apt` e `systemd`;
- Apache para servir a página;
- PHP para executar o código;
- MariaDB já instanciado com banco, tabela e registros;
- phpMyAdmin para inspeção local do banco;
- FTP local para demonstrar transferência de arquivos;
- painel protegido por usuário e senha do `.env`.

Cada cópia do laboratório procura portas host livres a partir do prefixo `55`. Assim, duas instalações podem coexistir: uma pode usar `55080/55021` e outra `55081/55022`, sem alterar as portas internas `80/21` da VM.

Containers ainda não fazem parte desta versão. A camada containerizada está em `VAGRANT-CONTAINERS.md` e representa uma evolução de implementação, não uma mudança no contrato que as aplicações consomem.

## Implementações

### 01. VM e stack web

Arquivos: `Vagrantfile`, `bootstrap.sh`, `database/init.sql` e `example/index.php`.

Resultado: uma VM Linux local com:

- Apache atendendo em `http://localhost:55080`;
- PHP executando a página de teste;
- MariaDB com o banco `curso_exemplo`;
- phpMyAdmin em `http://localhost:55080/phpmyadmin`;
- FTP local em `localhost:55021`.

O arquivo `VAGRANT.md` acompanha este laboratório como manual técnico de apoio. Ele contém a sequência de instalação, a explicação do `Vagrantfile`, o papel do `bootstrap.sh`, o script SQL, o teste feito por `example/index.php`, o tratamento de colisões de portas e os comandos de ciclo de vida.

### 02. Segunda implementação: containers

A próxima etapa reaproveita o comportamento da stack em `Dockerfile` e `compose.yaml`, mantendo a VM tradicional como referência de comparação. O documento [VAGRANT-CONTAINERS.md](VAGRANT-CONTAINERS.md) explica IaAS, IaC, Docker Desktop, Docker Engine, volumes e healthchecks.

O operador pode comparar a instalação tradicional com serviços declarados em `Dockerfile` e `compose.yaml`, preservando a mesma separação entre aplicação, dados e infraestrutura.

### 03. Execução em nuvem

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

O `.env` contém o exemplo didático `root` / `vrampp` e `admin` / `vrampp-admin` para esta VM. Ele é ignorado pelo Git e não deve ser enviado ao repositório. Em um projeto real, use secrets externos, senhas fortes e usuários com o menor privilégio necessário.

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

O dashboard pede autenticação HTTP e mostra LEDs dos serviços. Depois do login, permite subir, descer ou reiniciar Apache, MariaDB e FTP. Os controles são didáticos e limitados pelo wrapper sudo do provisionamento.

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
- `VAGRANT-CONTAINERS.md`: especificação da implementação containerizada;
- `.env.example`: modelo de configuração; o `.env` real nunca entra no Git.
- `smoke-test.ps1`: verificação HTTP do dashboard e endpoint de status.

A pasta `.vagrant/` é estado local gerado pelo Vagrant. Não deve ser criada manualmente, versionada ou enviada ao GitHub.

## Segurança

As credenciais do exemplo são somente locais e vêm de `.env`. Não versione `.env`, não reutilize `vrampp` fora da VM, não exponha MariaDB e não publique FTP sem TLS na internet. Em ambientes reais, prefira SFTP/SSH, HTTPS, secrets externos, usuários de banco com menor privilégio e uma política de firewall.

## Versão

`v0.5.0` é o marco atual: base Debian compacta, portas paralelas e painel autenticado. `v0.1.0` permanece como a primeira versão histórica da VM Vagrant.

## Licença e contato

Este projeto é distribuído sob a [licença MIT](LICENSE). Contato do Prof. Rold Jr.: `prof.roldjunior@gmail.com`.

## Referências

- Vagrant: https://developer.hashicorp.com/vagrant/docs
- VirtualBox: https://www.virtualbox.org/wiki/Documentation
- Debian: https://www.debian.org/doc/
- Apache: https://httpd.apache.org/docs/
- PHP: https://www.php.net/docs.php
- MariaDB: https://mariadb.com/docs/
