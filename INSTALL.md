# Deming installation procedure

## Recommended configuration

- OS : Debian 12 (stable) — recommended. Also tested on Ubuntu 24.04 LTS and RedHat-family distributions (RHEL, Rocky Linux, AlmaLinux 9).
- RAM : 2G
- Disk : 20G
- VCPU 2

## Installation

If you install Debian from scratch, a minimal profile is enough:
- without the Debian desktop environment
- with the Web server task
- with the SSH server task
- with standard system utilities

Note: a minimal Debian install does not include `sudo` by default. Either install it first as root
(`apt install sudo` then add your user to the `sudo` group), or replace `sudo` with `su -c "..."` in
the commands below.

Update linux distribution

    sudo apt update && sudo apt upgrade

Install Apache, git, php and composer

    sudo apt-get install git composer apache2 php-fpm php php-cli php-opcache php-mysql php-zip php-gd php-mbstring php-curl php-xml -y

On RedHat-family distributions (RHEL, Rocky Linux, AlmaLinux), use `dnf` instead of `apt-get` and
adapt package names accordingly, e.g.:

    sudo dnf install git composer httpd php-fpm php-cli php-opcache php-mysqlnd php-pdo php-gd php-mbstring php-curl php-xml -y

The rest of this guide uses Debian/Ubuntu (`apt`) commands and Apache directory paths; adjust them
for `httpd` on RedHat-family systems.

Create the project directory

    cd /var/www
    sudo mkdir deming
    sudo chown $USER:$GROUP deming

Clone project from Github

    git clone https://www.github.com/sourcentis/deming

Install packages with composer :

    cd deming
    mkdir -p storage/framework/views
    mkdir -p storage/framework/cache
    mkdir -p storage/framework/sessions
    mkdir -p bootstrap/cache
    composer install

## Database

Install MariaDB (works also with PostgreSQL and MySQL)

    sudo apt install mariadb-server

Start database client

    sudo mariadb

Create database _deming_ and user _deming_user_.

    CREATE DATABASE deming CHARACTER SET utf8 COLLATE utf8_general_ci;
    CREATE USER 'deming_user'@'localhost' IDENTIFIED BY 'demPasssword-123';
    GRANT ALL ON deming.* TO deming_user@localhost;
    GRANT PROCESS ON *.* TO 'deming_user'@'localhost';

    FLUSH PRIVILEGES;
    EXIT;

## Configuration

Create an .env file in the project root directory:

    cd /var/www/deming
    cp .env.example .env

Set database connection parameters :

    vi .env

    ## .env file
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=deming
    DB_USERNAME=deming_user
    DB_PASSWORD=demPasssword-123


## Create database

Run migrations

    LANG=en php artisan migrate --seed

Note: the seed is important (--seed), as it will create the first administrator user for you.

Generate application key

    php artisan key:generate

If you want to use the API, install [Laravel Passport](https://laravel.com/docs/11.x/passport) (optional) :

    php artisan passport:install

Create storage link

    php artisan storage:link

Import attributes

    LANG=en php artisan db:seed --class=AttributeSeeder

Then populate the database with 27001:2022 and generated tests data

    php artisan deming:import-framework ./storage/app/repository/ISO27001-2022.en.xlsx --clean
    php artisan deming:generate-tests --locale=en

The `--locale` option accepts `en` or `fr` and controls the language of the generated test data (risks, action plans, exceptions, audit observations). If omitted, it is guessed from the `LANG` environment variable, defaulting to `en`.

## Start application with PHP

    php artisan serve

or to access the application from another server

    php artisan serve --host 0.0.0.0 --port 8000

The application can be accessed at URL [http://127.0.0.1:8000]

    user : admin@admin.localhost
    password : admin

The administrator's default language is English. To change language, go to the user profile page
(top right of the main page).

To import a repository and generate test data, go to "Configuration" -> "Import" (optional).

## Start application with systemd

It is also possible to have the application start as a `systemd` service. For this you will need to create the service's defintion file:

	su root -c "vi /etc/systemd/system/deming.service"

and add it the following content:

	[Unit]
	Description=Deming
	After=network.target
	After=mariadb.service
	After=apache2.service

	[Service]
	Type=simple
	ExecStart=/usr/bin/php artisan serve --host 127.0.0.1 --port 8000
	User=www-data
	Group=www-data
	WorkingDirectory=/var/www/deming
	KillMode=mixed

	[Install]
	WantedBy=multi-user.target

Reload `systemd` to take this new service in account and start the application:

	systemctl daemon-reload
 	systemctl enable --now deming.service

## Apache

To configure Apache, modify the properties of the Deming directory and grant the appropriate permissions to the hive with the following command:

    sudo chown -R www-data:www-data /var/www/deming
    sudo chmod -R 775 /var/www/deming/storage

Next, create a new Apache virtual host configuration file to serve the application:

    sudo vi /etc/apache2/sites-available/deming.conf

Add the following lines:

    <VirtualHost *:80>
    ServerName deming.local
    ServerAdmin admin@example.com
    DocumentRoot /var/www/deming/public
    <Directory /var/www/deming>
    AllowOverride All
    </Directory>
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
    </VirtualHost>

Save and close the file when finished. Next, activate the Apache virtual host and rewrite module with the following commands (replace `8.2` with your installed PHP version — `php -v` — e.g. `8.3` on Ubuntu 24.04):

    sudo a2enmod rewrite
    sudo a2dissite 000-default.conf
    sudo a2ensite deming.conf
    sudo a2dismod php8.2
    sudo a2enmod proxy_fcgi setenvif
    sudo a2enconf php8.2-fpm

Finally, restart the Apache service to activate the changes:

    sudo systemctl restart apache2

## Apache with HTTPS configuration

In addition to the above instructions, create or modify the virtual host configuration's file:

    su root -c "vi /etc/apache2/sites-available/deming.conf"

and add the following content:

	<VirtualHost *:80>
	   ServerName deming.local

	    RewriteEngine On
	    RewriteRule ^/?(.*) https://%{SERVER_NAME}/$1
	</VirtualHost>

	<VirtualHost *:443>
	    ServerName deming.local
	    DocumentRoot /var/www/deming/public

	    Protocols h2 h2c http/1.1

	    <Directory /var/www/deming>
	        AllowOverride All
	    </Directory>

	    ProxyPass / http://127.0.0.1:8000/
	    ProxyPassReverse / http://127.0.0.1:8000/
	    ProxyPreserveHost On

 	    # If you use php-fpm, adapt the socket's path (PHP version) below and uncomment
	    #<FilesMatch \.php$>
	    #    SetHandler "proxy:unix:/var/run/php/php8.2-fpm.sock|fcgi://localhost/"
	    #</FilesMatch>

    	    ErrorLog ${APACHE_LOG_DIR}/error.log
    	    CustomLog ${APACHE_LOG_DIR}/access.log combined

	    <IfModule mod_headers.c>
	        Header always set Strict-Transport-Security "max-age=15552000; includeSubDomains; preload"
	        Header always set Referrer-Policy "no-referrer"
	        Header always set X-Content-Type-Options "no-sniff"
	        Header always set X-XSS-Protection "1; mode=block"
	        Header always set X-Robots-Tag "none"
	        Header always set X-Frame-Options "SAMEORIGIN"
	        Header edit Set-Cookie ^(.*)$ "$1;HttpOnly;Secure;SameSite=Strict"
	    </IfModule>

	    SSLEngine on
	    SSLCertificateFile  /etc/apache2/ssl/deming.local.crt
	    SSLCertificateKeyFile /etc/apache2/ssl/deming.local.key
	</VirtualHost>

## PHP

You need to set the value of upload_max_filesize and post_max_size in your php.ini
(/etc/php/8.2/fpm/php.ini — adjust the version to match `php -v`) :

    ; Maximum allowed size for uploaded files.
    upload_max_filesize = 10M

    ; Must be greater than or equal to upload_max_filesize
    post_max_size = 10M

After modifying php.ini file(s), you need to restart your php-fpm service to use the new configuration.

    sudo systemctl restart php-fpm

## Mail configuration

To send notification e-mails, configure the SMTP server in Deming, as administrator, in the
**Configuration → Settings** menu, **Notifications** tab:

* server and port, encryption (none, SSL or TLS) and automatic STARTTLS;
* authentication, user and password;
* optional DKIM signature: domain, selector, private key path and passphrase.

These settings are stored in the database; the password and the DKIM passphrase are encrypted with the `APP_KEY`.
The "Test" button sends a test e-mail to the current user with the values entered.

The `MAIL_*` variables of the `.env` file are only read once, by `php artisan migrate`, to initialize these settings
(fresh install or upgrade from a version that stored them in `.env`). They can be removed from `.env` afterwards:

```dotenv
MAIL_HOST='smtp.localhost'
MAIL_PORT=2525
MAIL_SMTP_AUTH=true
MAIL_ENCRYPTION='ssl'        # 'ssl', 'tls' or empty
MAIL_SMTP_AUTO_TLS=false
MAIL_USERNAME=
MAIL_PASSWORD=
```

Don't forget to [configure](https://sourcentis.github.io/deming/config/#notifications) the content and frequency of
your e-mails, in the same tab.

## LDAP / LDAPRecord configuration (optional)

This section lets you enable LDAP authentication in Deming using **LDAPRecord v2**. It works with **Active Directory** *and* **OpenLDAP**, and can coexist with local (database) authentication.

### Prerequisites

The PHP LDAP extension must be installed and enabled.

```bash
sudo apt-get install php-ldap
sudo systemctl restart apache2
```

### Environment

Add / adjust the following variables:

```dotenv
# Enable LDAP in Deming (hybrid mode)
LDAP_ENABLED=true                 # Turn LDAP authentication on
LDAP_FALLBACK_LOCAL=true          # If LDAP fails, try local DB auth
LDAP_AUTO_PROVISION=false         # Auto-create the local user after a successful LDAP bind

# LDAP server connection
LDAP_HOST=ldap.example.org
LDAP_PORT=389                     # 389 (StartTLS) or 636 (LDAPS)
LDAP_BASE_DN=dc=example,dc=org
LDAP_USERNAME=cn=admin,dc=example,dc=org   # Service account used for searches
LDAP_PASSWORD=********
LDAP_TLS=true                     # StartTLS (recommended when using port 389)
LDAP_SSL=false                    # true if you use ldaps:// on port 636
LDAP_TIMEOUT=5                    # (optional)

# Candidate attributes to identify the username entered in the form
# Order matters: the first match wins.
# OpenLDAP: uid, cn, mail ; AD: sAMAccountName, userPrincipalName, mail
LDAP_LOGIN_ATTRIBUTES=uid,cn,mail,sAMAccountName,userPrincipalName
```

# User must be member of this LDAP group
LDAP_GROUP=Mercator

**Examples**

* OpenLDAP (typical user DN: `uid=jdupont,ou=people,dc=example,dc=org`):

  ```dotenv
  LDAP_TLS=true
  LDAP_SSL=false
  LDAP_LOGIN_ATTRIBUTES=uid,cn,mail
  LDAP_GROUP=Merator
  ```

* Active Directory (UPN: `jdupont@example.org`, sAM: `jdupont`):

  ```dotenv
  LDAP_TLS=true
  LDAP_SSL=false
  LDAP_LOGIN_ATTRIBUTES=sAMAccountName,userPrincipalName,mail,cn
  LDAP_USERNAME=EXAMPLE\svc_ldap   # or the full DN of the service account
  ```

After changing `.env`:

```bash
php artisan config:clear
php artisan optimize:clear
```

### Certificates

If you use StartTLS/LDAPS with a private CA, add the CA to your OS trust store (e.g., place the file in `/usr/local/share/ca-certificates` and run `update-ca-certificates`).

### How it works in Deming

The authentication controller:

* searches the LDAP entry using an **OR** query across the attributes listed in `LDAP_LOGIN_ATTRIBUTES`;
* retrieves the **DN**;
* attempts a **bind** with the supplied password;
* on success, signs in the corresponding local application user (and can **auto-provision** it if it does not exist, depending on `LDAP_AUTO_PROVISION`).

### Quick test

Before testing via the UI, validate the connection from the CLI:

```bash
php artisan tinker
```

Then in Tinker:

```php
use LdapRecord\Container;

$dn = 'uid=jdupont,ou=people,dc=example,dc=org';  // or cn=..., or an AD DN
Container::getConnection()->auth()->attempt($dn, 'PASSWORD', true);
// => true expected if authentication succeeds
```

### Fast troubleshooting

* **Missing PHP LDAP**: `php -m | grep ldap` should print `ldap`. If not, `sudo apt-get install php-ldap`, then restart PHP/Apache.
* **Wrong DN**: make sure organizational units are correct (e.g., `ou=people`).
* **TLS/SSL**: StartTLS = `LDAP_TLS=true` (389). LDAPS = `LDAP_SSL=true` (636). Do not use cleartext in production.
* **Untrusted certificate**: add the CA to the system store (see above).
* **Cannot search**: verify `LDAP_USERNAME` / `LDAP_PASSWORD` (service account) and directory ACLs.
* **Login attribute**: align `LDAP_LOGIN_ATTRIBUTES` with what your users actually type (uid? UPN? email?).

### Security — do this

* Use **TLS/LDAPS**. Period.
* Restrict the **service account** to the minimum required (read-only on needed attributes).
* Avoid logging passwords or full DNs in application logs.
* If you enable **auto-provisioning**, store a random password in the DB (not a copy of the LDAP password) and enforce your role/profile policies.


## Keycloak Configuration (optional)

To configure [Keycloak](https://www.keycloak.org), follow these steps:

- Open your `.env` file.
- Modify the Keycloak configuration settings as follows:

```bash
SOCIALITE_PROVIDERS="keycloak"
KEYCLOAK_CLIENT_ID= # Client Id (on Keycloak)
KEYCLOAK_CLIENT_SECRET=  # Client Secret
KEYCLOAK_REDIRECT_URI=${APP_URL}auth/callback/keycloak
KEYCLOAK_BASE_URL=<KeyCloak IP Address>
KEYCLOAK_REALM=   # Realm Name
```

After adding `keycloak` to the `SOCIALITE_PROVIDERS` variable, a button will appear on the login page, allowing users to log in via Keycloak. (It is possible to modify the button text with the `KEYCLOAK_DISPLAY_NAME` variable).

To allow user creation and/or updates by Keycloak, add the following parameters:

```bash
KEYCLOAK_ALLOW_CREATE_USER=true
KEYCLOAK_ALLOW_UPDATE_USER=true
```

If you want to retrieve the user role provided by Keycloak during creation or update, it is necessary to request an additional `scope` and define the name of the `claim` that will contain the role:

```bash
KEYCLOAK_ADDITIONAL_SCOPES="roles"
KEYCLOAK_ROLE_CLAIM="resource_access.deming.roles.0"
```

It is also possible to provide a default role, used if Keycloak does not provide the role:

```bash
KEYCLOAK_DEFAULT_ROLE=<Possible value: auditee, auditor, user>
```

For more complete documentation on Keycloak configuration, consult the official Keycloak documentation.

## Configuration of a Generic OpenID Connect Provider

It is possible to add a generic OpenID Connect identity provider. Simply add `oidc` to the `SOCIALITE_PROVIDERS` variable. All the variables seen above exist, they start with `OIDC_` (see the `.env.example` file for more information).

## Sheduler

Modify crontab

    sudo crontab -e

add this line to crontab

    * * * * * cd /var/www/deming && php artisan schedule:run >> /dev/null 2>&1

## Update

To update Deming, go to the Deming directory and retrieve the sources

    cd /var/www/deming
    git pull

Migrate database

    php artisan migrate

When upgrading from a version that stored the SMTP server in `.env`, this migration copies the `MAIL_*` values into
the database, including the former names `MAIL_SMTP_SECURE` and `MAIL_AUTH`. Nothing else is needed: the SMTP server
is then managed in **Configuration → Settings → Notifications**.

Update libraries

    composer install

Empty caches

    php artisan optimize:clear

## Reset to zero

To start from an empty database with the ISO 27001:2022 standard.

Here's the command to recreate the DB:

    LANG=en php artisan migrate:fresh --seed

Import attributes

    LANG=en php artisan db:seed --class=AttributeSeeder

Then to populate the database with 27001:2022

    php artisan deming:import-framework ./storage/app/repository/ISO27001-2022.en.xlsx
