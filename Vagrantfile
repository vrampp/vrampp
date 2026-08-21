# VM compacta: Debian, Apache, PHP, MariaDB, phpMyAdmin e FTP.
require "socket"

def available_port(start_port)
  port = start_port
  loop do
    begin
      server = TCPServer.new("127.0.0.1", port)
      server.close
      return port
    rescue Errno::EADDRINUSE
      port += 1
    end
  end
end

Vagrant.configure("2") do |config|
  config.vm.box = "debian/bookworm64"
  config.vm.hostname = "vrampp-local"
  # Cada instalacao procura portas livres, preservando o prefixo 55.
  http_port = available_port(Integer(ENV.fetch("VRAMPP_HTTP_PORT", "55080")))
  ftp_port = available_port(Integer(ENV.fetch("VRAMPP_FTP_PORT", "55021")))
  config.vm.network "forwarded_port", guest: 80, host: http_port
  config.vm.network "forwarded_port", guest: 21, host: ftp_port

  # MariaDB continua interno por padrao.
  db_port = Integer(ENV.fetch("VRAMPP_DB_PORT", "0"))
  if db_port.positive?
    config.vm.network "forwarded_port", guest: 3306, host: db_port, auto_correct: true
  end

  config.vm.provider "virtualbox" do |virtualbox|
    virtualbox.memory = 1536
    virtualbox.cpus = 2
  end

  # Copia a pasta inteira do vrampp para dentro da VM.
  config.vm.synced_folder ".", "/vagrant/vrampp"
  config.vm.provision "shell", path: "bootstrap.sh", env: {
    "VRAMPP_EFFECTIVE_HTTP_PORT" => http_port.to_s,
    "VRAMPP_EFFECTIVE_FTP_PORT" => ftp_port.to_s,
    "VRAMPP_EFFECTIVE_DB_PORT" => db_port.to_s
  }
end