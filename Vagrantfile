# VM generica: Apache, PHP, MariaDB, phpMyAdmin e FTP instalados no guest.
Vagrant.configure("2") do |config|
  config.vm.box = "ubuntu/jammy64"
  config.vm.hostname = "vrampp-local"
  # A porta guest pode ser fixa; a porta host precisa estar livre.
  config.vm.network "forwarded_port", guest: 80, host: 8080, auto_correct: true
  config.vm.network "forwarded_port", guest: 21, host: 2121, auto_correct: true

  config.vm.provider "virtualbox" do |virtualbox|
    virtualbox.memory = 2048
    virtualbox.cpus = 2
  end

  # Copia a pasta inteira do myXampp para dentro da VM.
  config.vm.synced_folder ".", "/vagrant/myXampp"
  config.vm.provision "shell", path: "bootstrap.sh"
end