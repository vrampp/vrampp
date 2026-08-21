#!/usr/bin/env bash
set -euo pipefail

export DEBIAN_FRONTEND=noninteractive
PROJECT_ROOT=/vagrant/vrampp
ENV_FILE="$PROJECT_ROOT/.env"
if [ ! -f "$ENV_FILE" ]; then
	echo ".env nao encontrado. Copie .env.example para .env antes de executar vagrant provision." >&2
	exit 1
fi
ENV_FILE_CLEAN=$(mktemp)
trap 'rm -f "$ENV_FILE_CLEAN"' EXIT
tr -d '\r' < "$ENV_FILE" > "$ENV_FILE_CLEAN"
set -a
# shellcheck disable=SC1090
source "$ENV_FILE_CLEAN"
set +a
: "${DB_NAME:=curso_exemplo}"
: "${DB_USER:=root}"
: "${DB_PASSWORD:=vrampp}"
: "${DB_HOST:=127.0.0.1}"
: "${DB_PORT:=3306}"
: "${DB_EXPOSE:=false}"
: "${DB_REMOTE_USER:=vrampp_client}"
: "${DB_REMOTE_PASSWORD:=vrampp-client}"
: "${ADMIN_USER:=admin}"
: "${ADMIN_PASSWORD:=vrampp-admin}"
: "${VRAMPP_EFFECTIVE_HTTP_PORT:=55080}"
: "${VRAMPP_EFFECTIVE_FTP_PORT:=55021}"
: "${VRAMPP_EFFECTIVE_DB_PORT:=0}"
apt-get update
apt-get install -y apache2 apache2-utils libapache2-mod-php php-mysql mariadb-server phpmyadmin vsftpd
apt-get clean
rm -rf /var/lib/apt/lists/*
systemctl enable --now apache2 mariadb vsftpd

if [ "$DB_EXPOSE" = "true" ]; then
	cat > /etc/mysql/mariadb.conf.d/60-vrampp-network.cnf <<'MYSQL'
[mysqld]
bind-address = 0.0.0.0
MYSQL
	systemctl restart mariadb
fi

# Algumas versões do pacote não criam a entrada em conf-available.
# Declaramos o Alias explicitamente para o provisionamento ser reproduzível.
if [ -d /usr/share/phpmyadmin ]; then
	printf '%s\n' \
		'Alias /phpmyadmin /usr/share/phpmyadmin' \
		'' \
		'<Directory /usr/share/phpmyadmin>' \
		'    Options FollowSymLinks' \
		'    DirectoryIndex index.php' \
		'    AllowOverride All' \
		'    Require all granted' \
		'</Directory>' \
		> /etc/apache2/conf-available/phpmyadmin.conf
	a2enconf phpmyadmin
fi
a2enmod rewrite
a2enmod auth_basic authn_file
systemctl reload apache2

printf '%s\n' "${ADMIN_PASSWORD}" | htpasswd -i -c -B /etc/apache2/.vrampp.htpasswd "${ADMIN_USER}"
cat > /etc/apache2/conf-available/vrampp-auth.conf <<'APACHE'
<Directory /var/www/html>
	AuthType Basic
	AuthName "vrampp dashboard"
	AuthBasicProvider file
	AuthUserFile /etc/apache2/.vrampp.htpasswd
	Require valid-user
</Directory>
APACHE
a2enconf vrampp-auth
systemctl reload apache2

install -d -o www-data -g www-data /var/www/html
rm -f /var/www/html/index.html
test -f "$PROJECT_ROOT/example/index.php"
cp "$PROJECT_ROOT/example/index.php" /var/www/html/index.php
install -d -o www-data -g www-data /var/www/html/api
if [ -d "$PROJECT_ROOT/example/api" ]; then
	cp "$PROJECT_ROOT/example/api/"*.php /var/www/html/api/
fi
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

if sudo mariadb -e 'SELECT 1' >/dev/null 2>&1; then
	MYSQL=(sudo mariadb)
else
	MYSQL=(mariadb -uroot -p"$DB_PASSWORD")
fi
"${MYSQL[@]}" < "$PROJECT_ROOT/database/init.sql"
"${MYSQL[@]}" -e "ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD('${DB_PASSWORD}'); FLUSH PRIVILEGES;"
if [ "$DB_EXPOSE" = "true" ]; then
	"${MYSQL[@]}" -e "CREATE USER IF NOT EXISTS '${DB_REMOTE_USER}'@'%' IDENTIFIED BY '${DB_REMOTE_PASSWORD}'; GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_REMOTE_USER}'@'%'; FLUSH PRIVILEGES;"
fi

cat > /var/www/html/config.local.php <<PHP_CONFIG
<?php
return [
	'host' => '${DB_HOST}',
	'port' => '${DB_PORT}',
	'name' => '${DB_NAME}',
	'user' => '${DB_USER}',
	'password' => '${DB_PASSWORD}',
	'http_port' => '${VRAMPP_EFFECTIVE_HTTP_PORT}',
	'ftp_port' => '${VRAMPP_EFFECTIVE_FTP_PORT}',
	'db_port' => '${VRAMPP_EFFECTIVE_DB_PORT}',
];
PHP_CONFIG
chown www-data:www-data /var/www/html/config.local.php
chmod 640 /var/www/html/config.local.php

cat > /usr/local/sbin/vrampp-service <<'SERVICE'
#!/usr/bin/env bash
set -euo pipefail
action="${1:-}"
service="${2:-}"
case "$service" in apache2|mariadb|vsftpd) ;; *) exit 2 ;; esac
case "$action" in start|stop|restart) systemctl "$action" "$service" ;; status) systemctl is-active "$service" ;; *) exit 2 ;; esac
SERVICE
chmod 755 /usr/local/sbin/vrampp-service
printf '%s\n' 'www-data ALL=(root) NOPASSWD: /usr/local/sbin/vrampp-service *' > /etc/sudoers.d/vrampp-service
chmod 440 /etc/sudoers.d/vrampp-service

cat > /etc/apache2/conf-available/vrampp-protect.conf <<'APACHE'
<FilesMatch "^config\.local\.php$|^\.env$">
	Require all denied
</FilesMatch>
APACHE
a2enconf vrampp-protect
systemctl reload apache2

echo "VM vrampp provisionada em http://localhost:55080 e FTP em localhost:55021"