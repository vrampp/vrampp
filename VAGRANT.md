# vrampp: ambiente LAMP local com Vagrant

Este artigo apresenta uma VM Linux local com uma pequena pilha web instalada diretamente no sistema operacional: Apache, PHP, MariaDB, phpMyAdmin e FTP. A VM cria um banco de exemplo, popula três registros e publica uma página PHP que executa um `SELECT` e mostra uma lista.

O escopo é demonstrar, de ponta a ponta, as camadas de um ambiente web: máquina, sistema operacional, servidor HTTP, interpretador, banco, credenciais, tabela, consulta e resposta.

## 1. Resultado esperado

Ao terminar, esta sequência deve funcionar:

```text
Windows -> VirtualBox -> Vagrant -> Ubuntu -> Apache/PHP -> MariaDB -> phpMyAdmin/FTP -> SELECT -> HTML
```

Abra `http://localhost:8080` no Windows e veja a lista de produtos de exemplo. Nenhum container é necessário nesta primeira camada.

## 2. Modelo mental de infraestrutura

Uma aplicação web depende de camadas. Cada camada responde a uma pergunta:

| Camada | Pergunta | Recurso utilizado |
| --- | --- | --- |
| Máquina | Onde o sistema executa? | VM Ubuntu |
| Virtualização | Quem cria a VM? | VirtualBox |
| Automação | Como repetir a criação? | Vagrantfile |
| Sistema | Qual ambiente base? | Ubuntu Jammy |
| Servidor | Quem recebe HTTP? | Apache |
| Linguagem | Quem executa a página? | PHP |
| Dados | Onde ficam os registros? | MariaDB |
| Administração | Como inspecionar tabelas? | phpMyAdmin |
| Transferência | Como testar arquivos? | vsftpd/FTP local |
| Aplicação | Como lê os registros? | PDO + `SELECT` |

O Vagrant automatiza a máquina. O `bootstrap.sh` instala os serviços. A página PHP demonstra o comportamento final.

## 3. IaC, IaaS e DevOps

**IaaS** é o modelo em que um provedor entrega recursos como VMs, discos e redes por uma plataforma ou API. AWS e Oracle Cloud são exemplos.

**IaC** é a prática de descrever infraestrutura com arquivos versionados e executáveis. Neste ambiente, `Vagrantfile` e `bootstrap.sh` formam o IaC local.

O fluxo DevOps usado aqui é:

```text
Planejar -> Codificar -> Provisionar -> Testar -> Executar -> Observar
```

Esta primeira etapa é deliberadamente tradicional. Ela torna visível o que depois será empacotado no Dockerfile e no Compose.

## 4. Dependências do host

O Vagrant precisa de um provider de virtualização. Usaremos VirtualBox:

```text
Windows
  -> VirtualBox: motor da VM
    -> Vagrant: automação
      -> Ubuntu Jammy: guest
        -> Apache + PHP + MariaDB
```

Instale na estação Windows:

```powershell
winget install --id Oracle.VirtualBox --exact --accept-source-agreements --accept-package-agreements
winget install --id Hashicorp.Vagrant --exact --accept-source-agreements --accept-package-agreements
VBoxManage --version
vagrant --version
```

VirtualBox é uma dependência do host, não da VM. Sem um provider, o Vagrant não tem onde executar o guest. A virtualização de hardware, Intel VT-x ou AMD-V, deve estar habilitada.

## 5. Estrutura dos arquivos

Entre nesta pasta:

```text
myXampp/
  .gitignore
  VAGRANT.md
  Vagrantfile
  bootstrap.sh
  database/
    init.sql
  example/
    index.php
```

Os arquivos formam um ambiente independente. Copie a pasta inteira `myXampp`, abra um terminal dentro dela e execute `vagrant up`; não é necessário copiar arquivos adicionais.

O diretório `.vagrant/` é criado automaticamente pelo Vagrant para guardar o estado da VM. Não o crie manualmente, não o distribua e não o versiona: o `.gitignore` já o exclui. Se ele aparecer após um teste, isso é esperado.

## 6. O Vagrantfile do myXampp

```ruby
# VM genérica: Apache, PHP, MariaDB, phpMyAdmin e FTP instalados no guest.
Vagrant.configure("2") do |config|
  config.vm.box = "ubuntu/jammy64"
  config.vm.hostname = "vrampp-local"
  config.vm.network "forwarded_port", guest: 80, host: 8080, auto_correct: true
  config.vm.network "forwarded_port", guest: 21, host: 2121, auto_correct: true

  config.vm.provider "virtualbox" do |virtualbox|
    virtualbox.memory = 2048
    virtualbox.cpus = 2
  end

  config.vm.synced_folder ".", "/vagrant/myXampp"
  config.vm.provision "shell", path: "bootstrap.sh"
end
```

### `config.vm.box`

Escolhe a box base `ubuntu/jammy64`. Na primeira execução, o Vagrant baixa a imagem; nas seguintes, reutiliza o cache local.

### `hostname`

Define um nome previsível para a VM e facilita diagnóstico.

### `forwarded_port`

Encaminha a porta 80 do Apache na VM para a porta 8080 do Windows:

```text
Windows localhost:8080 -> guest port 80 -> Apache
Windows localhost:2121 -> guest port 21 -> vsftpd
```

As portas representam lados diferentes da comunicação:

- **guest:** porta dentro da VM, onde Apache ou vsftpd escutam;
- **host:** porta no Windows, usada pelo navegador ou cliente FTP.

O guest pode usar a porta 80 em várias VMs diferentes, porque cada VM possui sua própria rede virtual. Já a porta host pertence ao Windows inteiro. Dois programas não podem escutar simultaneamente o mesmo endereço e porta, por exemplo `0.0.0.0:8080`.

## 6.1 Colisão de portas

Se a porta host estiver ocupada, o Vagrant exibirá uma mensagem semelhante a esta:

```text
==> default: Setting the name of the VM: BOLINHA_default_1787268375083_19943
Vagrant cannot forward the specified ports on this VM, since they
would collide with some other application that is already listening
on these ports. The forwarded port to 8080 is already in use
on the host machine.
```

Isso significa que a VM pode até ter sido criada, mas o encaminhamento `Windows:8080 -> VM:80` não pôde ser estabelecido. Causas comuns:

- outro Vagrant já está usando a porta;
- Docker Desktop publicou um container nessa porta;
- Apache, IIS ou outro servidor web está ativo no Windows;
- uma aplicação de desenvolvimento está ouvindo em `8080`;
- uma execução anterior ficou com um processo ou container aberto.

### Ver portas ocupadas no Windows

No PowerShell, listar conexões TCP em escuta:

```powershell
Get-NetTCPConnection -State Listen |
  Sort-Object LocalPort |
  Format-Table LocalAddress, LocalPort, OwningProcess
```

Consultar especificamente a porta 8080:

```powershell
Get-NetTCPConnection -LocalPort 8080 -ErrorAction SilentlyContinue |
  Format-Table LocalAddress, LocalPort, State, OwningProcess
```

Descobrir o programa associado ao PID retornado:

```powershell
Get-Process -Id 1234
```

Substitua `1234` pelo valor de `OwningProcess`.

Alternativa usando o `netstat`:

```powershell
netstat -ano | Select-String ':8080'
tasklist /FI "PID eq 1234"
```

Para verificar as duas portas deste ambiente:

```powershell
Get-NetTCPConnection -LocalPort 8080,2121 -ErrorAction SilentlyContinue |
  Format-Table LocalAddress, LocalPort, State, OwningProcess
```

### Resolver a colisão

1. **Parar o recurso que ocupa a porta.** Por exemplo, parar uma VM ou container que não está sendo usado:

```powershell
vagrant halt
docker ps
docker stop NOME_OU_ID
```

2. **Escolher outra porta no host.** A porta da VM permanece igual; somente a porta externa muda:

```ruby
config.vm.network "forwarded_port", guest: 80, host: 8081, auto_correct: true
config.vm.network "forwarded_port", guest: 21, host: 2122, auto_correct: true
```

Depois, os acessos serão `http://localhost:8081` e FTP em `localhost:2122`.

3. **Usar `auto_correct`.** O `Vagrantfile` genérico está configurado para permitir que o Vagrant escolha a próxima porta livre quando a porta preferida estiver ocupada. A mensagem pode indicar algo como:

```text
Warning: Connection to 8080 failed. Trying again...
Forwarding ports...
guest: 80 => host: 8081
```

Isso é conveniente para desenvolvimento, mas o endereço final deixa de ser previsível. Sempre confira a saída do `vagrant up` ou execute:

```powershell
vagrant port
```

Em documentação, testes automatizados e produção, é melhor definir a porta explicitamente e resolver a causa da colisão.

### `provider`

Entrega CPU e memória ao guest e escolhe VirtualBox como motor de virtualização. Vagrant automatiza; VirtualBox executa.

### `synced_folder`

Monta a pasta inteira `myXampp` em `/vagrant/myXampp`. Assim, `example/index.php`, `database/init.sql` e os demais arquivos ficam disponíveis dentro da VM sem cópia manual.

### `provision`

Executa `bootstrap.sh` dentro da VM. O script instala Apache, PHP, MariaDB, phpMyAdmin e vsftpd diretamente no guest.

## 7. O bootstrap e o banco

O script realiza seis tarefas:

1. instala `apache2`, `libapache2-mod-php`, `php-mysql`, `mariadb-server`, `phpmyadmin` e `vsftpd`;
2. habilita e inicia Apache e MariaDB;
3. copia a página PHP para `/var/www/html/index.php`;
4. configura phpMyAdmin em `/phpmyadmin`;
5. configura FTP local sem acesso anônimo;
6. executa `database/init.sql`, que cria o banco, o usuário, a tabela e os registros de exemplo.

O banco usado é `curso_exemplo`, com a tabela criada em `database/init.sql`:

```sql
products(id, name, category)
```

O script usa `CREATE IF NOT EXISTS` e inserções condicionais para poder ser reaplicado sem duplicar os registros.

## 8. A página PHP e o SELECT

A página usa PDO para abrir a conexão:

```php
$pdo = new PDO(
    'mysql:host=localhost;dbname=curso_exemplo;charset=utf8mb4',
    'curso',
    'curso-local'
);
$products = $pdo->query(
    'SELECT id, name, category FROM products ORDER BY id'
)->fetchAll();
```

O PHP percorre o resultado e produz HTML. `example/index.php` é o teste integrado do banco: se os três nomes aparecerem em `http://localhost:8080`, Apache, PHP, PDO, usuário, tabela e `SELECT` estão funcionando juntos.

## 9. phpMyAdmin e FTP

Com a VM ligada, acesse `http://localhost:8080/phpmyadmin`. Use o usuário `curso` e a senha `curso-local` para consultar o banco `curso_exemplo`. Essas credenciais são exclusivas do laboratório.

O vsftpd não permite acesso anônimo e fica disponível no host pela porta `2121`:

```text
Host: localhost
Porta: 2121
Usuário: vagrant
Senha: a senha do usuário vagrant
Diretório: /var/www/html
```

Um cliente FTP pode testar upload e download. O serviço é apenas local e não deve ser exposto à internet; em ambientes reais, prefira SFTP/SSH ou HTTPS. O phpMyAdmin também deve ser protegido por rede, autenticação e HTTPS fora do laboratório.

## 10. Subir a VM

Abra o PowerShell na pasta `myXampp`:

```powershell
cd A:\WSLS\CONTATICA\_dev\aulas\02-iac-e-primeira-publicação\vagrant\myXampp
vagrant validate
vagrant up
```

Na primeira execução, aguarde o download da box e o provisionamento. Depois valide:

```powershell
vagrant status
vagrant ssh
```

Dentro da VM:

```bash
systemctl status apache2 --no-pager
systemctl status mariadb --no-pager
curl --fail http://localhost
exit
```

No Windows, abra `http://localhost:8080`. A tabela deve mostrar três produtos.

## 11. Comandos de ciclo de vida

Execute na pasta `myXampp`:

```powershell
vagrant status
vagrant halt
vagrant up
vagrant reload
vagrant provision
vagrant ssh
vagrant destroy
```

- `status`: mostra o estado;
- `halt`: desliga a VM sem apagar seu disco;
- `up`: cria ou inicia;
- `reload`: reinicia e aplica mudanças de rede/configuração;
- `provision`: reaplica o `bootstrap.sh`;
- `ssh`: acessa o guest;
- `destroy`: remove a VM.

Use `vagrant halt` para terminar a sessão normalmente. Use `vagrant destroy` para reconstruir do zero. Dados no disco virtual podem ser perdidos ao destruir.

## 12. Idempotência, estado e reprodutibilidade

O diretório `.vagrant` guarda metadados locais do provider e não deve entrar no Git. O `Vagrantfile` descreve o estado desejado; o script executa detalhes imperativos.

Idempotência significa que repetir `vagrant provision` não deve criar uma segunda tabela ou duplicar registros. Reprodutibilidade significa que outra pessoa, com as mesmas dependências, consegue obter uma VM equivalente.

Uma VM local não representa toda a produção. Firewall, DNS, HTTPS, backup, observabilidade e disponibilidade serão responsabilidades da camada Oracle no commit 03.

## 13. Segurança da demonstração

As credenciais são didáticas e locais. Não reutilize `curso-local` em qualquer ambiente externo. Não publique MariaDB na rede e não coloque segredos reais no `Vagrantfile` ou no Git.

## 14. Verificações adicionais

1. Troque a porta do host para 8081 e acesse `http://localhost:8081`.
2. Adicione um quarto registro em `database/init.sql` e execute `vagrant provision`.
3. Explique a diferença entre a VM, o Apache, o PHP e o MariaDB.
4. Desenhe o caminho do navegador até o `SELECT`.
5. Destrua e recrie a VM e explique por que o código retorna, mas os dados locais podem desaparecer.

## 15. camada containerizada

Vagrant e Docker Compose controlam camadas diferentes. Vagrant cria a VM; Docker Engine executa os containers dentro dela; Compose descreve os serviços, a rede, os volumes e as dependências.

| Pergunta | Vagrant | Terraform | Compose |
| --- | --- | --- | --- |
| O que cria? | VM local | Recursos de cloud e rede | Containers e serviços |
| Onde executa? | Provider local | API do provider | Docker Engine |
| Provider comum | VirtualBox | AWS, Oracle, Azure | Docker |
| Arquivo principal | `Vagrantfile` | arquivos `.tf` | `compose.yaml` |
| Estado | `.vagrant` e provider | state do Terraform | estado do Docker |

No Windows, Docker Desktop pode executar a topologia diretamente. Em Linux ou dentro de uma VM Vagrant, o Docker Engine e o plugin Compose são instalados no guest:

```bash
sudo apt-get update
sudo apt-get install -y docker.io docker-compose-plugin
sudo systemctl enable --now docker
sudo usermod -aG docker "$USER"
```

Depois de sair e entrar novamente na sessão:

```bash
docker --version
docker compose version
docker compose config
docker compose up --build -d
docker compose ps
```

O container web acessa MariaDB pelo nome do serviço `database`, não por `localhost`. Dentro de um container, `localhost` aponta para o próprio container. Volumes guardam dados; imagens guardam a aplicação. `docker compose down --volumes` remove também os dados locais.

Essa camada é a transição entre a instalação tradicional deste documento e uma entrega empacotada: o `Dockerfile` define a imagem, o `compose.yaml` define os serviços e o pipeline pode testar a mesma imagem em Linux, Windows e nuvem.

## 16. Referências

- Vagrant - documentação oficial: https://developer.hashicorp.com/vagrant/docs
- Vagrant - boxes: https://developer.hashicorp.com/vagrant/docs/boxes
- Vagrant - providers: https://developer.hashicorp.com/vagrant/docs/providers
- Vagrant - provisionamento: https://developer.hashicorp.com/vagrant/docs/provisioning
- VirtualBox - documentação: https://www.virtualbox.org/wiki/Documentation
- Ubuntu Server - documentação: https://documentation.ubuntu.com/server/
- Apache HTTP Server - documentação: https://httpd.apache.org/docs/
- PHP - manual oficial: https://www.php.net/docs.php
- PDO - manual oficial: https://www.php.net/manual/en/book.pdo.php
- MariaDB - documentação: https://mariadb.com/docs/
- Docker - documentação: https://docs.docker.com/
- Terraform - documentação: https://developer.hashicorp.com/terraform/docs

## 17. Caso de uso final: fazendo um XAMPP

## Objetivo

O pacote `myXampp` monta um ambiente web local em uma VM Ubuntu, sem containers:

```text
VirtualBox -> Vagrant -> Ubuntu -> Apache + PHP + MariaDB + phpMyAdmin + vsftpd
```

Ao final:

- `http://localhost:8080` mostra uma lista obtida por `SELECT`;
- `http://localhost:8080/phpmyadmin` permite inspecionar o banco;
- FTP responde em `localhost:2121` para testes locais;
- o banco `curso_exemplo` contém a tabela `products` e três registros.

## arquivos do pacote

| Arquivo | Responsabilidade |
| --- | --- |
| `Vagrantfile` | define box, hostname, portas, recursos, pasta e provisionamento |
| `bootstrap.sh` | instala e configura Apache, PHP, MariaDB, phpMyAdmin e vsftpd |
| `example/index.php` | executa PDO + `SELECT` e renderiza a lista HTML |
| `VAGRANT.md` | documento combinado com teoria e passo a passo |

## Instalação

No Windows, instalar as dependências uma vez:

```powershell
winget install --id Oracle.VirtualBox --exact --accept-source-agreements --accept-package-agreements
winget install --id Hashicorp.Vagrant --exact --accept-source-agreements --accept-package-agreements
VBoxManage --version
vagrant --version
```

## Subir o ambiente

No diretório `myXampp`:

```powershell
vagrant validate
vagrant up
vagrant status
```

O provisionamento instala os pacotes no guest e executa o SQL inicial. A primeira execução pode demorar por causa do download da box e dos pacotes Ubuntu.

## Verificar a página e o banco

```powershell
Invoke-WebRequest http://localhost:8080
```

Abra `http://localhost:8080` no navegador. A página deve exibir `Caderno de projetos`, `Curso de PHP` e `Laboratorio DevOps`.

Para verificar o serviço por dentro da VM:

```powershell
vagrant ssh
```

```bash
systemctl status apache2 --no-pager
systemctl status mariadb --no-pager
systemctl status vsftpd --no-pager
curl --fail http://localhost
sudo mariadb -e 'SELECT id, name, category FROM curso_exemplo.products ORDER BY id;'
exit
```

## Verificar phpMyAdmin

Abra:

```text
http://localhost:8080/phpmyadmin
```

Credenciais locais da demonstração:

```text
Usuário: curso
Senha: curso-local
Banco: curso_exemplo
```

Essas credenciais são descartáveis e não devem ser usadas fora da VM local.

## Verificar FTP

O serviço usa vsftpd, bloqueia acesso anônimo e encaminha a porta 21 do guest para a porta 2121 do Windows:

```text
Host: localhost
Porta: 2121
Usuário: vagrant
Senha: senha do usuário vagrant
Diretório: /var/www/html
```

Use FileZilla ou outro cliente FTP apenas para testar transferência local. FTP sem TLS não deve ser exposto à internet; em um ambiente real, use SFTP.

## Parar, reiniciar e remover

```powershell
vagrant halt
vagrant up
vagrant provision
vagrant destroy
```

`halt` desliga e preserva a VM. `provision` reaplica o script. `destroy` remove a VM e os dados locais no disco virtual.

## Diagnóstico rápido

Se `vagrant up` informar que a porta 8080 está ocupada:

```powershell
Get-NetTCPConnection -LocalPort 8080 -ErrorAction SilentlyContinue |
  Format-Table LocalAddress, LocalPort, State, OwningProcess
Get-Process -Id 1234
```

A porta HTTP pode ser corrigida no `Vagrantfile`, por exemplo para 8081. A porta FTP pode ser alterada de 2121 para 2122.

## Limite do exemplo

Este pacote demonstra instalação tradicional em uma VM. A entrega 02 do Contatica substitui a instalação manual por `Dockerfile` e `compose.yaml`; a entrega 03 promove a imagem para Oracle Cloud Free Tier.

