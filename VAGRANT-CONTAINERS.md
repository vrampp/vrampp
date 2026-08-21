# vrampp v2: segunda camada com containers

Depois de executar a primeira versao Vagrant, o curso apresenta o `vrampp v2` com containers. A pergunta muda de “como instalar Apache, PHP e MariaDB na VM?” para “como descrever e executar os servicos sem instalar a stack no host?”. Este arquivo deve acompanhar o repositorio vrampp e ser referenciado pelo Contatica no commit 03.

## O que muda

| Camada | VM generica | Containers |
| --- | --- | --- |
| Maquina | Vagrant + VirtualBox | Windows com Docker Desktop ou Linux com Docker Engine |
| Servicos | `apt-get` no guest | `Dockerfile` e `compose.yaml` |
| Banco | MariaDB instalado na VM | container MariaDB + volume |
| Aplicacao | `/var/www/html` da VM | imagem PHP/Apache |
| IaC | `Vagrantfile` e `bootstrap.sh` | `Dockerfile` e `compose.yaml` |

Na entrega 03 do Contatica, a mesma aplicacao PHP/MariaDB do commit 02 roda nessa camada containerizada. O commit 02 continua usando o vrampp Vagrant.

## Windows com Docker Desktop

```powershell
winget install --id Docker.DockerDesktop --exact --accept-source-agreements --accept-package-agreements
docker --version
docker compose version
docker info
```

No diretório do commit 02:

```powershell
Copy-Item .env.example .env
docker compose config
docker compose up --build -d
docker compose ps
Invoke-WebRequest http://localhost:8080
```

## Linux ou VM Vagrant com Docker

Dentro de uma VM Linux, instalar somente o runtime:

```bash
sudo apt-get update
sudo apt-get install -y docker.io docker-compose-plugin
sudo systemctl enable --now docker
sudo usermod -aG docker "$USER"
```

Sair e entrar novamente na sessão SSH, depois executar na pasta do commit 02:

```bash
docker compose config
docker compose up --build -d
docker compose ps
curl --fail http://localhost:8080
```

## Ciclo de vida

```bash
docker compose stop
docker compose start
docker compose logs --tail 50 web database
docker compose down
docker compose down --volumes
```

`down --volumes` apaga o banco local. A imagem contém a aplicação; o volume contém os dados.

## IaAS e IaC nesta camada

O Docker Desktop fornece uma plataforma local de execucao, semelhante a uma camada de infraestrutura como servico para o laboratorio. O IaC aparece em tres arquivos com responsabilidades diferentes:

- `Dockerfile`: descreve a imagem da aplicacao;
- `compose.yaml`: descreve web, banco, rede, volume e healthcheck;
- pasta `iac/` do Contatica: escolhe o alvo e conecta o produto ao contrato do vrampp.

O mesmo contrato pode ser executado no Windows, em uma VM Linux ou na cloud. O que muda e o executor e a configuracao externa, nao a historia de provisionamento.

## Por que esta é a segunda camada

A VM generica prova a instalação tradicional. A entrega containerizada prova empacotamento, isolamento, volume, healthcheck e paridade entre Windows, Linux e pipeline. O produto passa a ser executado por artefatos, não por uma lista manual de instalações.

## Ponte para o commit 04

No commit 04, o Compose será reaproveitado na VM Oracle Cloud Free Tier. A diferença será trocar `build` local por `image` publicada e configurar secrets remotos. O código funcional do Contatica não será refeito.

## Ponte para o commit 05

No commit 05, a esteira de desenvolvimento publicará no vrampp e a esteira de produção promoverá a imagem aprovada para a nuvem. O vrampp vira o destino de validação contínua; a cloud vira o destino de produção.