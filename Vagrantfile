# VM generica: Apache, PHP, MariaDB, phpMyAdmin e FTP instalados no guest.
Vagrant.configure("2") do |config|
  config.vm.box = "ubuntu/jammy64"
  config.vm.hostname = "vrampp-local"
  # O prefixo 55 identifica o laboratorio; as portas permanecem validas (1..65535).
  http_port = Integer(ENV.fetch("VRAMPP_HTTP_PORT", "55080"))
  ftp_port = Integer(ENV.fetch("VRAMPP_FTP_PORT", "55021"))
  config.vm.network "forwarded_port", guest: 80, host: http_port, auto_correct: true
  config.vm.network "forwarded_port", guest: 21, host: ftp_port, auto_correct: true

  # MariaDB continua interno por padrao. Ative somente para estudar NAT:
  # VRAMPP_DB_PORT=55306 vagrant up
  db_port = Integer(ENV.fetch("VRAMPP_DB_PORT", "0"))
  if db_port.positive?
    config.vm.network "forwarded_port", guest: 3306, host: db_port, auto_correct: true
  end

  config.vm.provider "virtualbox" do |virtualbox|
    virtualbox.memory = 2048
    virtualbox.cpus = 2
  end

  # Copia a pasta inteira do vrampp para dentro da VM.
  config.vm.synced_folder ".", "/vagrant/vrampp"
  config.vm.provision "shell", path: "bootstrap.sh"
end