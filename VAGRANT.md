# vrampp: curso prático de Vagrant e infraestrutura

Este manual é o curso prático do projeto `vrampp`, criado pelo Prof. Rold Jr. para ensinar DevOps a partir de entregas pequenas e verificáveis. A primeira versão apresenta uma VM Debian Bookworm compacta com uma pequena pilha web instalada diretamente no sistema operacional: Apache, PHP, MariaDB, phpMyAdmin e FTP. A VM cria um banco de exemplo, popula três registros e publica uma landing page PHP que executa um `SELECT` e mostra uma lista.

O escopo é demonstrar, de ponta a ponta, as camadas de um ambiente web: máquina, sistema operacional, servidor HTTP, interpretador, banco, credenciais, tabela, consulta e resposta.

## Como estudar este projeto

Leia cada seção como uma etapa de uma entrega de projeto:

1. entenda o resultado esperado;
2. identifique qual arquivo controla a infraestrutura;
3. execute a mudança com `vagrant up` ou `vagrant provision`;
4. verifique a página, o banco e os serviços;
5. registre o que foi aprendido antes de avançar para containers.

Nesta primeira etapa, a pergunta central é: **como uma descrição versionada consegue criar uma máquina que entrega uma aplicação web funcional?**

## Limite desta versão

O `vrampp` atual é deliberadamente tradicional. O Vagrant cria a VM e o `bootstrap.sh` instala Apache, PHP, MariaDB, phpMyAdmin e vsftpd dentro dela. Ainda não há Docker, Compose, pipeline ou publicação em nuvem nesta pasta. Esses elementos entram nas próximas entregas para que a comparação seja observável, e não apenas conceitual.

## 1. Resultado esperado

Ao terminar, esta sequência deve funcionar:

```text
Windows -> VirtualBox -> Vagrant -> Debian -> Apache/PHP -> MariaDB -> phpMyAdmin/FTP -> SELECT -> HTML
```

Abra `http://localhost:55080` no Windows e veja a lista de produtos de exemplo. Nenhum container é necessário nesta primeira camada.

## 2. Modelo mental de infraestrutura

Uma aplicação web depende de camadas. Cada camada responde a uma pergunta:

| Camada | Pergunta | Recurso utilizado |
| --- | --- | --- |
| Máquina | Onde o sistema executa? | VM Debian |
| Virtualização | Quem cria a VM? | VirtualBox |
| Automação | Como repetir a criação? | Vagrantfile |
| Sistema | Qual ambiente base? | Debian Bookworm |
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

Antes de subir a VM, crie a configuração local:

```powershell
Copy-Item .env.example .env
```

O `.env` é lido pelo `bootstrap.sh` dentro da VM e gera `config.local.php`, que não é distribuído pelo Git. O repositório oferece `.env.example` apenas como modelo de nomes e valores. Nesta demonstração, `root` / `vrampp` são credenciais didáticas do banco local e `admin` / `vrampp-admin` protegem o painel; nunca use essas senhas em produção, não envie `.env` para o Git e não cole secrets em issues ou pull requests.

O Vagrant precisa de um provider de virtualização. Usaremos VirtualBox:

```text
Windows
  -> VirtualBox: motor da VM
    -> Vagrant: automação
      -> Debian Bookworm: guest
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
vrampp/
  .gitignore
  .env.example
  VAGRANT.md
  Vagrantfile
  bootstrap.sh
  database/
    init.sql
  example/
    index.php
```

Os arquivos formam um ambiente independente. Copie a pasta inteira `vrampp`, abra um terminal dentro dela e execute `vagrant up`; não é necessário copiar arquivos adicionais.

O diretório `.vagrant/` é criado automaticamente pelo Vagrant para guardar o estado da VM. Não o crie manualmente, não o distribua e não o versiona: o `.gitignore` já o exclui. Se ele aparecer após um teste, isso é esperado.

## 6. O Vagrantfile do vrampp

```ruby
# VM genérica: Apache, PHP, MariaDB, phpMyAdmin e FTP instalados no guest.
Vagrant.configure("2") do |config|
  config.vm.box = "debian/bookworm64"
  config.vm.hostname = "vrampp-local"
  config.vm.network "forwarded_port", guest: 80, host: 55080, auto_correct: true
  config.vm.network "forwarded_port", guest: 21, host: 55021, auto_correct: true

  config.vm.provider "virtualbox" do |virtualbox|
    virtualbox.memory = 2048
    virtualbox.cpus = 2
  end

  config.vm.synced_folder ".", "/vagrant/vrampp"
  config.vm.provision "shell", path: "bootstrap.sh"
end
```

### `config.vm.box`

Escolhe a box base `debian/bookworm64`. Na primeira execução, o Vagrant baixa a imagem; nas seguintes, reutiliza o cache local.

### `hostname`

Define um nome previsível para a VM e facilita diagnóstico.

### `forwarded_port`

Encaminha a porta 80 do Apache na VM para a porta 55080 do Windows:

```text
Windows localhost:55080 -> guest port 80 -> Apache
Windows localhost:55021 -> guest port 21 -> vsftpd
```

As portas representam lados diferentes da comunicação:

- **guest:** porta dentro da VM, onde Apache ou vsftpd escutam;
- **host:** porta no Windows, usada pelo navegador ou cliente FTP.

### Portas internas, portas expostas e NAT

Uma porta pertence ao sistema que está escutando nela. Dentro do Debian guest, os serviços usam suas portas naturais:

| Serviço | Porta interna no Ubuntu | Porta exposta no Windows |
| --- | ---: | ---: |
| Apache/PHP | `80` | `55080` |
| MariaDB | `3306` | não exposta por padrão |
| FTP/vsftpd | `21` | `55021` |
| SSH | `22` | gerenciada pelo Vagrant; consulte `vagrant port` |

O encaminhamento é um NAT/port forwarding do VirtualBox. Quando o navegador acessa `localhost:55080`, o Windows entrega o tráfego ao adaptador NAT da VM, e o VirtualBox encaminha para `guest:80`. O Apache nunca precisa escutar `55080` dentro do Debian. Da mesma forma, um cliente FTP usa `localhost:55021`, mas o vsftpd continua escutando `21` no guest.

O MariaDB é deliberadamente diferente: o banco fica acessível apenas dentro da VM em `127.0.0.1:3306`. Isso reduz a superfície de ataque. Um programa de banco no Windows não deve tentar `localhost:3306`, porque esse endereço aponta para o Windows, não para o Debian guest.

### Instalações paralelas

O `Vagrantfile` procura uma porta TCP livre para cada serviço no host, começando pelos valores do `.env`. A porta interna não muda: Apache continua em `80` e FTP em `21`. Se `55080` estiver ocupada, a próxima instalação usa `55081`; se `55021` estiver ocupada, usa `55022`. O Vagrant injeta as portas efetivas no provisionamento, e o dashboard as exibe.

Cada instalação precisa estar em uma pasta própria e possuir sua própria VM. Para descobrir as portas de uma cópia:

```powershell
vagrant port
```

Para estudar um acesso direto, o exemplo de NAT usa `55306` no host e `3306` no guest:

```powershell
$env:VRAMPP_DB_PORT = "55306"
vagrant up
```

Esse encaminhamento só cria o caminho de rede. Para o MariaDB aceitar conexões externas, `.env` também precisa usar `DB_EXPOSE=true`; o provisionamento então escuta em `0.0.0.0` e cria o usuário remoto definido em `DB_REMOTE_USER`. Use esse modo apenas em laboratório e conecte com o usuário remoto, nunca com `root`.

No cliente de banco, a conexão será `127.0.0.1:55306`, usuário `vrampp_client` e a senha definida no `.env`. O servidor continua vendo sua porta interna como `3306`.

Para SSH, o caminho mais seguro é o túnel, sem publicar MariaDB:

```powershell
vagrant ssh
vagrant port
ssh -L 55306:127.0.0.1:3306 vagrant@127.0.0.1 -p PORTA_SSH
```

Com o túnel ativo, o cliente de banco acessa `127.0.0.1:55306`, mas o tráfego percorre SSH e termina em `127.0.0.1:3306` dentro da VM. Substitua `PORTA_SSH` pelo valor exibido por `vagrant port`. O túnel é preferível a abrir MariaDB em uma interface de rede.

O guest pode usar a porta 80 em várias VMs diferentes, porque cada VM possui sua própria rede virtual. Já a porta host pertence ao Windows inteiro. Dois programas não podem escutar simultaneamente o mesmo endereço e porta, por exemplo `0.0.0.0:55080`.

## 6.1 Colisão de portas

Se a porta host estiver ocupada, o Vagrant exibirá uma mensagem semelhante a esta:

```text
==> default: Setting the name of the VM: BOLINHA_default_1787268375083_19943
Vagrant cannot forward the specified ports on this VM, since they
would collide with some other application that is already listening
on these ports. The forwarded port to 55080 is already in use
on the host machine.
```

Isso significa que a VM pode até ter sido criada, mas o encaminhamento `Windows:55080 -> VM:80` não pôde ser estabelecido. Causas comuns:

- outro Vagrant já está usando a porta;
- Docker Desktop publicou um container nessa porta;
- Apache, IIS ou outro servidor web está ativo no Windows;
- uma aplicação de desenvolvimento está ouvindo em `55080`;
- uma execução anterior ficou com um processo ou container aberto.

### Ver portas ocupadas no Windows

No PowerShell, listar conexões TCP em escuta:

```powershell
Get-NetTCPConnection -State Listen |
  Sort-Object LocalPort |
  Format-Table LocalAddress, LocalPort, OwningProcess
```

Consultar especificamente a porta 55080:

```powershell
Get-NetTCPConnection -LocalPort 55080 -ErrorAction SilentlyContinue |
  Format-Table LocalAddress, LocalPort, State, OwningProcess
```

Descobrir o programa associado ao PID retornado:

```powershell
Get-Process -Id 1234
```

Substitua `1234` pelo valor de `OwningProcess`.

Alternativa usando o `netstat`:

```powershell
netstat -ano | Select-String ':55080'
tasklist /FI "PID eq 1234"
```

Para verificar as duas portas deste ambiente:

```powershell
Get-NetTCPConnection -LocalPort 55080,55021 -ErrorAction SilentlyContinue |
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
config.vm.network "forwarded_port", guest: 80, host: 55081, auto_correct: true
config.vm.network "forwarded_port", guest: 21, host: 55022, auto_correct: true
```

Depois, os acessos serão `http://localhost:55081` e FTP em `localhost:55022`.

3. **Usar `auto_correct`.** O `Vagrantfile` genérico está configurado para permitir que o Vagrant escolha a próxima porta livre quando a porta preferida estiver ocupada. A mensagem pode indicar algo como:

```text
Warning: Connection to 55080 failed. Trying again...
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

Monta a pasta inteira `vrampp` em `/vagrant/vrampp`. Assim, `example/index.php`, `database/init.sql` e os demais arquivos ficam disponíveis dentro da VM sem cópia manual.

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
  "mysql:host=127.0.0.1;port=3306;dbname=curso_exemplo;charset=utf8mb4",
  'root',
  'vrampp'
);
$products = $pdo->query(
    'SELECT id, name, category FROM products ORDER BY id'
)->fetchAll();
```

O PHP percorre o resultado e produz HTML. `example/index.php` é o teste integrado do banco: se os três nomes aparecerem em `http://localhost:55080`, Apache, PHP, PDO, usuário, tabela e `SELECT` estão funcionando juntos.

A mesma página apresenta LEDs de operação. O cartão Apache/PHP prova que a requisição chegou e foi executada; MariaDB fica verde quando o PDO conecta e executa a consulta; phpMyAdmin representa o painel publicado pelo Apache; FTP faz um teste de socket na porta 21 do guest. As portas exibidas são as portas do host Windows: HTTP `55080` e FTP `55021`.

## 9. phpMyAdmin e FTP

Com a VM ligada, acesse `http://localhost:55080`. O navegador solicitará `ADMIN_USER` e `ADMIN_PASSWORD` definidos no `.env`; essa autenticação protege o dashboard e o endpoint de serviços. Depois do login, acesse phpMyAdmin pelo link do painel e use `root`/`DB_PASSWORD` somente dentro da VM local.

O vsftpd não permite acesso anônimo e fica disponível no host pela porta `55021`:

```text
Host: localhost
Porta: 55021
Usuário: vagrant
Senha: a senha do usuário vagrant
Diretório: /var/www/html
```

Um cliente FTP pode testar upload e download. O serviço é apenas local e não deve ser exposto à internet; em ambientes reais, prefira SFTP/SSH ou HTTPS. O phpMyAdmin também deve ser protegido por rede, autenticação e HTTPS fora do laboratório.

## 10. Subir a VM

Abra o PowerShell na pasta `vrampp`:

```powershell
cd A:\WSLS\CONTATICA\_dev\aulas\02-iac-e-primeira-publicação\vagrant\vrampp
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

No Windows, abra `http://localhost:55080`. A tabela deve mostrar três produtos.

## 11. Comandos de ciclo de vida

Execute na pasta `vrampp`:

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

### O que muda sem destruir a VM

Use `vagrant provision` quando mudar `bootstrap.sh`, `database/init.sql`, a página PHP, a configuração gerada ou qualquer arquivo que o script copie/configure dentro do guest. O provisionamento reaplica a receita sem apagar o disco virtual; é o ciclo normal de manutenção do projeto.

Use `vagrant reload` quando mudar rede, portas, hostname, memória, CPUs ou outra configuração do `Vagrantfile` que precise reiniciar a VM. A VM continua existindo e os dados do disco permanecem.

Use `vagrant halt` no fim do dia ou para liberar CPU e memória. Ele apenas desliga a VM; no próximo dia, `vagrant up` a inicia novamente.

Use `vagrant destroy` somente quando quiser reconstruir do zero, trocar a box, corrigir estado corrompido ou testar a instalação inicial. Ele apaga a VM e pode apagar os dados MariaDB armazenados no disco virtual. Antes, exporte dados importantes.

Exemplos de manutenção diária:

```powershell
# Verificar sem alterar
vagrant status
vagrant port

# Alterou PHP, SQL, .env ou bootstrap
vagrant provision

# Alterou Vagrantfile, portas ou recursos
vagrant reload --provision

# Pausa normal
vagrant halt

# Reabrir no dia seguinte
vagrant up
```

Erros comuns e resposta:

- `/.env: line ... $'\r': command not found`: o arquivo foi salvo com CRLF e o Bash recebeu o caractere de retorno do Windows. Salve `.env` com LF, ou normalize antes do provisionamento; nunca remova a validação do arquivo.
- `Vagrant cannot forward the specified ports`: a porta host está ocupada. Consulte `Get-NetTCPConnection`, pare o processo ou escolha outra porta.
- a página padrão do sistema aparece: o Apache ainda tem `index.html` padrão ou o provisionamento não foi reaplicado. Execute `vagrant provision` e confirme `/var/www/html/index.php` dentro do guest.
- o banco não conecta: confira o `.env`, o estado de `mariadb` e se a aplicação usa `127.0.0.1:3306` dentro da VM, não a porta exposta do Windows.
- o serviço não sobe: use `vagrant ssh`, `systemctl status apache2 mariadb vsftpd` e `journalctl -u SERVICO --no-pager`.

## 12. Idempotência, estado e reprodutibilidade

O diretório `.vagrant` guarda metadados locais do provider e não deve entrar no Git. O `Vagrantfile` descreve o estado desejado; o script executa detalhes imperativos.

Idempotência significa que repetir `vagrant provision` não deve criar uma segunda tabela ou duplicar registros. Reprodutibilidade significa que outra pessoa, com as mesmas dependências, consegue obter uma VM equivalente.

## 12.1 Versionamento do IaC junto com o sistema

O `Vagrantfile`, o `bootstrap.sh`, o schema e a aplicação formam uma unidade versionada. Quando alguém baixa um commit antigo do sistema, também baixa o provisionamento correspondente àquele estado. Isso é intencional: o código de uma versão deve encontrar a infraestrutura capaz de executá-lo.

Por isso, uma alteração de infraestrutura deve ser revisada junto com o código e registrada no mesmo commit ou em uma sequência explicitamente documentada. Não se deve usar um `bootstrap.sh` atual para tentar executar arbitrariamente um commit antigo sem verificar compatibilidade.

No futuro Contatica, a versão 2 poderá usar este vrampp tradicional; a versão 3 poderá usar containers. Ao fazer checkout de uma versão antiga, o procedimento correto será também executar o IaC daquela versão e, se necessário, `vagrant provision` para refazer o ambiente Vagrant. A VM pode então ser atualizada sem apagar tudo, desde que o contrato de dados e serviços seja compatível. Se a versão exigir uma arquitetura incompatível, destrua e recrie conscientemente.

Uma VM local não representa toda a produção. Firewall, DNS, HTTPS, backup, observabilidade e disponibilidade serão responsabilidades da camada Oracle no commit 03.

## 13. Segurança da demonstração

As credenciais são didáticas e locais. Não reutilize o valor de `DB_PASSWORD` em qualquer ambiente externo. Não publique MariaDB na rede e não coloque segredos reais no `Vagrantfile` ou no Git.

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
- Debian - documentação: https://www.debian.org/doc/
- Apache HTTP Server - documentação: https://httpd.apache.org/docs/
- PHP - manual oficial: https://www.php.net/docs.php
- PDO - manual oficial: https://www.php.net/manual/en/book.pdo.php
- MariaDB - documentação: https://mariadb.com/docs/
- Docker - documentação: https://docs.docker.com/
- Terraform - documentação: https://developer.hashicorp.com/terraform/docs

## Diagnóstico rápido

Se `vagrant up` informar que a porta 55080 está ocupada:

```powershell
Get-NetTCPConnection -LocalPort 55080 -ErrorAction SilentlyContinue |
  Format-Table LocalAddress, LocalPort, State, OwningProcess
Get-Process -Id 1234
```

A porta HTTP pode ser corrigida no `Vagrantfile`, por exemplo para 55081. A porta FTP pode ser alterada de 55021 para 55022.

## Limite do exemplo

Este pacote demonstra instalação tradicional em uma VM. A entrega 02 do Contatica substitui a instalação manual por `Dockerfile` e `compose.yaml`; a entrega 03 promove a imagem para Oracle Cloud Free Tier.

