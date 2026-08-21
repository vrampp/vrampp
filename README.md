# vrampp

Ambiente web local para aprender Vagrant, provisionamento e fundamentos de uma stack LAMP. O nome combina **Vagrant** com a ideia de um XAMPP próprio, executado em uma VM Ubuntu.

O repositório funciona como laboratório independente: clone o projeto, consulte o [VAGRANT.md](VAGRANT.md), instale VirtualBox e Vagrant e execute `vagrant up`. O Markdown é o material de apoio do projeto: explica os conceitos, descreve cada arquivo, orienta a execução e mostra como diagnosticar problemas.

O objetivo é aprender Vagrant praticando. A criação de uma stack LAMP própria é um exercício útil porque reúne vários recursos do Vagrant em um único ambiente: box, provider, VM, hostname, portas encaminhadas, pasta compartilhada, provisionamento, serviços Linux, banco de dados e ciclo de vida. O resultado é uma aplicação pequena, mas o aprendizado está em construir, verificar, parar, recriar e entender cada camada.

O `vrampp` não pretende substituir uma distribuição XAMPP pronta. Ele torna explícito como uma solução desse tipo é montada e permite observar o caminho completo entre a máquina host, a VM, o Apache, o PHP, o MariaDB e a página que consulta dados reais.

## Laboratórios

### 01. VM e stack web

Arquivos: `Vagrantfile`, `bootstrap.sh`, `database/init.sql` e `example/index.php`.

Resultado: uma VM Linux local com:

- Apache atendendo em `http://localhost:8080`;
- PHP executando a página de teste;
- MariaDB com o banco `curso_exemplo`;
- phpMyAdmin em `http://localhost:8080/phpmyadmin`;
- FTP local em `localhost:2121`.

O arquivo `VAGRANT.md` acompanha este laboratório como manual técnico de apoio. Ele contém a sequência de instalação, a explicação do `Vagrantfile`, o papel do `bootstrap.sh`, o script SQL, o teste feito por `example/index.php`, o tratamento de colisões de portas e os comandos de ciclo de vida.

### 02. Containers

A próxima etapa reaproveita o comportamento da stack em `Dockerfile` e `compose.yaml`, mantendo a VM tradicional como referência de comparação. Esta pasta contém o laboratório Vagrant; a entrega containerizada do Contatica permanece no material de produto correspondente.

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

Subir o laboratório:

```powershell
vagrant validate
vagrant up
vagrant status
```

Verificar a aplicação:

```powershell
Invoke-WebRequest http://localhost:8080
```

Abrir no navegador:

- `http://localhost:8080`: página PHP com `SELECT` em `products`;
- `http://localhost:8080/phpmyadmin`: administração do banco;
- `localhost:2121`: FTP local, sem acesso anônimo.

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
├── database/
│   └── init.sql
└── example/
    └── index.php
```

- `Vagrantfile`: define a box, recursos, rede, portas e provisionamento;
- `bootstrap.sh`: instala e configura a stack no guest;
- `database/init.sql`: cria banco, usuário, tabela e registros iniciais;
- `example/index.php`: teste integrado de PHP, PDO, MariaDB e HTML;
- `VAGRANT.md`: manual completo de conceitos, operação, diagnóstico e caso de uso.

A pasta `.vagrant/` é estado local gerado pelo Vagrant. Não deve ser criada manualmente, versionada ou enviada ao GitHub.

## Segurança

As credenciais do exemplo são somente locais. Não reutilize `curso-local`, não exponha MariaDB e não publique FTP sem TLS na internet. Em ambientes reais, prefira SFTP/SSH, HTTPS, secrets externos e uma política de firewall.

## Versão

`v0.1.0` é o primeiro marco funcional do laboratório: uma VM Vagrant reproduzível com uma stack LAMP local e documentação técnica para acompanhar cada etapa.

## Referências

- Vagrant: https://developer.hashicorp.com/vagrant/docs
- VirtualBox: https://www.virtualbox.org/wiki/Documentation
- Ubuntu Server: https://documentation.ubuntu.com/server/
- Apache: https://httpd.apache.org/docs/
- PHP: https://www.php.net/docs.php
- MariaDB: https://mariadb.com/docs/
