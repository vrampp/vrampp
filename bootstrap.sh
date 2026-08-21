#!/usr/bin/env bash
set -euo pipefail

export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get install -y apache2 libapache2-mod-php php-mysql mariadb-server phpmyadmin vsftpd
systemctl enable --now apache2 mariadb vsftpd

a2enconf phpmyadmin
a2enmod rewrite
systemctl reload apache2

install -d -o www-data -g www-data /var/www/html
cp /vagrant/myXampp/example/index.php /var/www/html/index.php
chown www-data:www-data /var/www/html/index.php

cat > /etc/vsftpd.conf <<'FTP'
listen=NO
listen_ipv6=YES
anonymous_enable=NO
local_enable=YES
write_enable=YES
local_umask=022
use_localtime=YES
chroot_local_user=YES
allow_writeable_chroot=YES
local_root=/var/www/html
FTP

usermod -s /bin/bash vagrant
systemctl restart vsftpd

sudo mariadb < /vagrant/myXampp/database/init.sql

echo "VM vrampp provisionada em http://localhost:8080 e FTP em localhost:2121"