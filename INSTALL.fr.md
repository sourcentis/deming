# Procédure d'installation de Deming

## Configuration recommandée

- OS : Debian 12 (stable) — recommandé. Également testé sur Ubuntu 24.04 LTS et les distributions de la famille RedHat (RHEL, Rocky Linux, AlmaLinux 9).
- RAM : 2G
- Disque : 20G
- VCPU 2

## Installation

Si vous installez Debian à partir de zéro, un profil minimal suffit :
- sans environnement de bureau Debian
- avec la tâche « serveur Web »
- avec la tâche « serveur SSH »
- avec les utilitaires système usuels

Remarque : une installation Debian minimale n'inclut pas `sudo` par défaut. Installez-le d'abord en
tant que root (`apt install sudo` puis ajoutez votre utilisateur au groupe `sudo`), ou remplacez
`sudo` par `su -c "..."` dans les commandes ci-dessous.

Mettre à jour la distribution linux

    sudo apt update && sudo apt upgrade

Installer Apache, git, php et composer

    sudo apt-get install git composer apache2 php-fpm php php-cli php-opcache php-mysql php-zip php-gd php-mbstring php-curl php-xml -y

Sur les distributions de la famille RedHat (RHEL, Rocky Linux, AlmaLinux), utilisez `dnf` à la place
de `apt-get` et adaptez les noms de paquets en conséquence, par exemple :

    sudo dnf install git composer httpd php-fpm php-cli php-opcache php-mysqlnd php-pdo php-gd php-mbstring php-curl php-xml -y

La suite de ce guide utilise les commandes Debian/Ubuntu (`apt`) et les chemins d'Apache ; adaptez-les
pour `httpd` sur les systèmes de la famille RedHat.

Créer le répertoire du projet

    cd /var/www
    sudo mkdir deming
    sudo chown $USER:$GROUP deming

Cloner le projet depuis Github

    git clone https://www.github.com/sourcentis/deming

Créer les répertoires temporaires

    cd deming
    mkdir -p storage/framework/views
    mkdir -p storage/framework/cache
    mkdir -p storage/framework/sessions
    mkdir -p bootstrap/cache

Installer les packages avec composer

    composer install

## Database

Installer la base de données (fonctionne aussi avec PostgreSQL et MySQL)

    sudo apt install mariadb-server

Lancer MariaDB avec les droits root

    sudo mariadb

Créer la base de données _deming_ et l'utilisateur _deming_user_

    CREATE DATABASE deming CHARACTER SET utf8 COLLATE utf8_general_ci;
    CREATE USER 'deming_user'@'localhost' IDENTIFIED BY 'demPasssword-123';
    GRANT ALL ON deming.* TO deming_user@localhost;
    GRANT PROCESS ON *.* TO 'deming_user'@'localhost';

    FLUSH PRIVILEGES;
    EXIT;

## Configuration

Créer un fichier .env dans le répertoire racine du projet :

    cd /var/www/deming
    cp .env.example .env

Mettre les paramètre de connexion à la base de données :

    vi .env

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=deming
DB_USERNAME=deming_user
DB_PASSWORD=demPasssword-123
```

## Créer la base de données

Exécuter les migrations

    LANG=fr php artisan migrate --seed

Remarque: la graine est importante (--seed), car elle créera le premier utilisateur administrateur pour vous.

Générer la clé de l'application

    php artisan key:generate

Si vous voulez utiliser l'API, installez [Laravel Passport](https://laravel.com/docs/11.x/passport) (option) :

    php artisan passport:install

Créer le lien de stockage

	php artisan storage:link

## Peupler la base de données

Pour importer la base de données avec les attributs de sécurité de la norme 27001:2022 (optionel)

    LANG=fr php artisan db:seed --class=AttributeSeeder

Peupler la base de données avec la norme ISO 27001:2022 et générer un jeu de tests (optionel)

    php artisan deming:import-framework ./storage/app/repository/ISO27001-2022.fr.xlsx --clean
    php artisan deming:generate-tests --locale=fr

L'option `--locale` accepte `fr` ou `en` et détermine la langue des données de test générées (risques, plans d'action, exceptions, observations d'audit). Si elle est omise, la langue est déduite de la variable d'environnement `LANG`, avec l'anglais par défaut.

## Démarrer l'application avec PHP

    php artisan serve

ou pour y accéder à l'application depuis un autre serveur

    php artisan serve --host 0.0.0.0 --port 8000

L'application est accessible à l'URL [http://127.0.0.1:8000]

    utilisateur : admin@admin.localhost
    mot de passe : admin

L'administrateur utilise la langue anglaise par défaut. Pour changer de langue, allez dans la page de profil de l'utilisateur
(en haut à droite de la page principale).

Pour importer un référentiel et générer des données de test, allez dans "Configuration" -> "Import" (optionel).

## Démarrer l'application avec systemd

Il est également possible de faire démarrer l'application en tant que service `systemd`. Pour cela, créez un nouveau fichier de définition du service :

	su root -c "vi /etc/systemd/system/deming.service"

Ajoutez les lignes suivantes:

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

Prenez en compte ce nouveau service et démarrez l'application :

	systemctl daemon-reload
 	systemctl enable --now deming.service

## Apache

Pour configurer Apache, modifiez les propriétés du répertoire Deming et accordez les autorisations appropriées au répertoire de stockage avec la commande suivante :

    sudo chown -R www-data:www-data /var/www/deming
    sudo chmod -R 775 /var/www/deming/storage

Ensuite, créez un nouveau fichier de configuration d'hôte virtuel Apache pour servir l'application :

    sudo vi /etc/apache2/sites-available/deming.conf

Ajouter les lignes suivantes :

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

Enregistrez et fermez le fichier lorsque vous avez terminé. Ensuite, activez l'hôte virtuel Apache et le module de réécriture avec les commandes suivantes (remplacez `8.2` par votre version de PHP installée — `php -v` — par exemple `8.3` sur Ubuntu 24.04) :

    sudo a2enmod rewrite
    sudo a2dissite 000-default.conf
    sudo a2ensite deming.conf
    sudo a2dismod php8.2
    sudo a2enmod proxy_fcgi setenvif
    sudo a2enconf php8.2-fpm

Enfin, redémarrez le service Apache pour activer les modifications :

    sudo systemctl restart apache2

## Apache avec configuration HTTPS

En complément des étapes ci-dessus, créez ou modifiez le fichier de configuration d'hôte virtuel Apache pour servir l'application :

    su root -c "vi /etc/apache2/sites-available/deming.conf"

Ajouter les lignes suivantes :

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

 	    # Si vous utilisez php-fpm (chemin de la socket et version PHP à adapter à votre cas)
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

Dans le cadre de cette configuration servant l'application en HTTPS, il pourra être nécessaire de positionner la variable `APP_ENV` à la valeur `production` dans le fichier `.env` de Deming.

## PHP

Vous devez définir les valeurs de upload_max_filesize et post_max_size dans votre php.ini
(/etc/php/8.2/fpm/php.ini — adaptez la version à `php -v`) :

    ; Taille maximale autorisée pour les fichiers téléchargés.
    upload_max_filesize = 10M

    ; Doit être supérieur ou égal à upload_max_filesize
    post_max_size = 10M

Après avoir modifié le(s) fichier(s) php.ini, vous devez redémarrer le service php-fpm  pour utiliser la nouvelle configuration.

    sudo systemctl restart php-fpm

## Configuration du mail

Pour envoyer les e-mails de notification, configurez le serveur SMTP dans Deming, en tant qu'administrateur, par le
menu **Configuration → Paramètres**, onglet **Notifications** :

* serveur et port, chiffrement (aucun, SSL ou TLS) et STARTTLS automatique ;
* authentification, utilisateur et mot de passe ;
* signature DKIM facultative : domaine, sélecteur, chemin de la clé privée et phrase secrète.

Ces paramètres sont enregistrés dans la base de données ; le mot de passe et la phrase secrète DKIM sont chiffrés
avec l'`APP_KEY`. Le bouton « Test » envoie un e-mail de test à l'utilisateur courant avec les valeurs saisies.

Les variables `MAIL_*` du fichier `.env` ne sont lues qu'une seule fois, par `php artisan migrate`, pour initialiser
ces paramètres (nouvelle installation ou mise à jour depuis une version qui les stockait dans `.env`). Elles peuvent
ensuite être retirées du `.env` :

```dotenv
MAIL_HOST='smtp.localhost'
MAIL_PORT=2525
MAIL_SMTP_AUTH=true
MAIL_ENCRYPTION='ssl'        # 'ssl', 'tls' ou vide
MAIL_SMTP_AUTO_TLS=false
MAIL_USERNAME=
MAIL_PASSWORD=
```

N'oubliez pas de [configurer](https://sourcentis.github.io/deming/fr/config/#notifications) le contenu et la
fréquence d'envoi des mails, dans le même onglet.

## LDAP / LDAPRecord configuration (optional)

Cette section te permet d’activer l’authentification LDAP dans Deming avec **LDAPRecord v2**. Elle fonctionne indifféremment avec **Active Directory** *ou* **OpenLDAP**, et peut cohabiter avec l’authentification locale (base de données).

### Prérequis

Prérequis : l’extension PHP LDAP doit être installée et active.

```bash
sudo apt-get install php-ldap
sudo systemctl restart apache2
```
### Environnement

Ajouter / adapter les variables suivantes :

```dotenv
# Activation LDAP dans Deming (mode hybride)
LDAP_ENABLED=true                 # Active l’authentification LDAP
LDAP_FALLBACK_LOCAL=true          # Si LDAP échoue, tenter l’auth locale
LDAP_AUTO_PROVISION=false         # Créer automatiquement l’utilisateur local après bind LDAP OK

# Connexion au serveur LDAP
LDAP_HOST=ldap.example.org
LDAP_PORT=389                     # 389 (StartTLS) ou 636 (LDAPS)
LDAP_BASE_DN=dc=example,dc=org
LDAP_USERNAME=cn=admin,dc=example,dc=org   # Compte de service pour la recherche
LDAP_PASSWORD=********
LDAP_TLS=true                     # StartTLS (recommandé si port 389)
LDAP_SSL=false                    # true si tu utilises ldaps:// sur 636
LDAP_TIMEOUT=5                    # (optionnel)

# Attributs candidats pour identifier l’utilisateur saisi dans le formulaire
# L’ordre a de l’importance : le premier qui matche est utilisé.
# OpenLDAP: uid, cn, mail ; AD: sAMAccountName, userPrincipalName, mail
LDAP_LOGIN_ATTRIBUTES=uid,cn,mail,sAMAccountName,userPrincipalName
```

**Exemples**

* OpenLDAP (DN utilisateur typique : `uid=jdupont,ou=people,dc=example,dc=org`) :

  ```dotenv
  LDAP_TLS=true
  LDAP_SSL=false
  LDAP_LOGIN_ATTRIBUTES=uid,cn,mail
  ```
* Active Directory (UPN : `jdupont@example.org`, sAM : `jdupont`) :

  ```dotenv
  LDAP_TLS=true
  LDAP_SSL=false
  LDAP_LOGIN_ATTRIBUTES=sAMAccountName,userPrincipalName,mail,cn
  LDAP_USERNAME=EXAMPLE\\svc_ldap   # ou DN complet du compte de service
  ```

Après modification du `.env` :

```bash
php artisan config:clear
php artisan optimize:clear
```

### Certificats

**Certificats** : si tu utilises StartTLS/LDAPS avec un CA interne, ajoute le CA au trust store de ta distribution (ex. `/usr/local/share/ca-certificates` + `update-ca-certificates`).

### Comment ça marche côté Deming

Le contrôleur d’authentification :

* recherche l’entrée LDAP via une **OR**-query sur les attributs listés dans `LDAP_LOGIN_ATTRIBUTES` ;
* récupère le **DN** ;
* tente un **bind** avec le mot de passe saisi ;
* si OK, il connecte l’utilisateur applicatif local correspondant (et peut **auto-provisionner** s’il n’existe pas, selon `LDAP_AUTO_PROVISION`).

### Test rapide

Avant de tester via l’UI, valide la connexion en CLI :

```bash
php artisan tinker
```

Puis dans Tinker :

```php
use LdapRecord\Container;

$dn = 'uid=jdupont,ou=people,dc=example,dc=org';  // ou cn=..., ou DN AD
Container::getConnection()->auth()->attempt($dn, 'MOT_DE_PASSE', true);
// => true attendu si l’authentification réussit
```

### Dépannage express

* **PHP LDAP manquant** : `php -m | grep ldap` doit renvoyer `ldap`. Sinon `sudo apt-get install php-ldap`, puis redémarrage PHP/Apache.
* **Mauvais DN** : ajoute les `ou=...` corrects (ex. `ou=people`).
* **TLS/SSL** : StartTLS = `LDAP_TLS=true` (389). LDAPS = `LDAP_SSL=true` (636). Ne laisse pas en clair en prod.
* **Certificat non approuvé** : ajoute le CA au système (voir ci-dessus).
* **Recherche impossible** : vérifie `LDAP_USERNAME` / `LDAP_PASSWORD` (compte de service) et les ACL de l’annuaire.
* **Attribut de login** : aligne `LDAP_LOGIN_ATTRIBUTES` avec ce que tes utilisateurs saisissent réellement (uid ? UPN ? email ?).

### Sécurité – à faire, pas à discuter

* Utilise **TLS/LDAPS**. Point.
* Restreins les droits du **compte de service** au strict nécessaire (lecture des attributs requis).
* Évite de logger des mots de passe ou DN complets côté application.
* Si tu actives l’**auto-provisioning**, stocke un mot de passe aléatoire côté base (pas un duplicat du LDAP) et applique tes politiques de rôles/profils.


## Configuration de Keycloak (optionnel)

Pour configurer [Keycloak](https://www.keycloak.org), suivez ces étapes :

- Ouvrez votre fichier .env.
- Modifiez les paramètres de configuration de Keycloak comme suit :

```bash
SOCIALITE_PROVIDERS="keycloak"
KEYCLOAK_CLIENT_ID= # Client Id (on Keycloak)
KEYCLOAK_CLIENT_SECRET=  # Client Secret
KEYCLOAK_REDIRECT_URI=${APP_URL}auth/callback/keycloak
KEYCLOAK_BASE_URL=<KeyCloak IP Address>
KEYCLOAK_REALM=   # Realm Name
```

Après avoir ajouter `keycloak` à la variable `SOCIALITE_PROVIDERS` un bouton apparaîtra sur la page de connexion, permettant aux utilisateurs de se connecter via Keycloak. (Il est possible de modifier le texte du bouton avec la variable `KEYCLAOK_DISPLAY_NAME`).

Pour autoriser la création d'utilisateur et/ou la mise à jour par Keycloak ajouter les paramètre suivants :

```bash
KEYCLOAK_ALLOW_CREATE_USER=true
KEYCLOAK_ALLOW_UPDATE_USER=true
```

Si vous souhaitez que récupérer le rôle de l'utilisateur fourni par Keycloak lors de sa création ou la mise à jour, il est nécessaire de lui de demander un `scope` supplémentaires et de définir le nom `claim` qui contiendra le rôle :
```bash
KEYCLOAK_ADDITIONAL_SCOPES="roles"
KEYCLOAK_ROLE_CLAIM="resource_access.deming.roles.0"
```

Il est également possible de fournir un rôle par défaut, utilisé si Keycloak ne fournit pas le rôle :
```bash
KEYCLOAK_DEFAULT_ROLE=<Valeur possible : auditee, auditor, user>
```

Pour une documentation plus complète sur la configuration de Keycloak, consultez la documentation officielle de Keycloak.

## Configuration d'un fournisseur OpenID Connect Générique

Il est possiblie d'ajouter un founisseur d'identité OpenID Connect générique, il suffit d'ajouter `oidc` à la variable `SOCIALITE_PROVIDERS`. Toutes les variables vu ci-dessus existe, elles commencent par `OIDC_` (voir le fichier .env.example pour plus d'information)

## Sheduler

Modifier le crontab

    sudo crontab -e

ajouter cette ligne dans le crontab

    * * * * * cd /var/www/deming && php artisan schedule:run >> /dev/null 2>&1

## Mise à jour

Pour mettre à jour Deming, il faut aller dans le répertoire de Deming et récupérer les sources

    cd /var/www/deming
    git pull

Migrer la base de données

    php artisan migrate

Lors d'une mise à jour depuis une version qui stockait le serveur SMTP dans `.env`, cette migration copie les valeurs
`MAIL_*` dans la base de données, y compris les anciens noms `MAIL_SMTP_SECURE` et `MAIL_AUTH`. Rien d'autre n'est à
faire : le serveur SMTP se gère ensuite dans **Configuration → Paramètres → Notifications**.

Mettre à jour les librairies

    composer install

Vider les caches

    php artisan optimize:clear

## Remise à zéro

Pour repartir d'une base de données vide avec la norme ISO 27001:2022.

Voici la commande pour recréer la DB :

    LANG=fr php artisan migrate:fresh --seed

Puis importer les attributs

    LANG=fr php artisan db:seed --class=AttributeSeeder

Peupler la base de données avec la norme ISO 27001:2022

    php artisan deming:import-framework ./storage/app/repository/ISO27001-2022.fr.xlsx
