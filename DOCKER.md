# Deming — Docker Deployment Guide

> **Deming** is an open-source ISMS management tool (ISO 27001 / NIS 2) built on Laravel.
> This guide covers everything you need to run it with Docker Compose, using the
> `docker-compose.yml` shipped at the root of this repository — a **production-oriented
> stack designed for Portainer**, with a manually pre-built image and an HAProxy
> reverse proxy in front for TLS termination.

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Architecture Overview](#architecture-overview)
3. [Quick Start](#quick-start)
4. [Environment Configuration](#environment-configuration)
5. [Build](#build)
6. [Environment Variables in docker-compose.yml](#environment-variables-in-docker-composeyml)
7. [Start](#start)
8. [Stop](#stop)
9. [Ports](#ports)
10. [First Connection](#first-connection)
11. [LDAP Authentication Restricted to a Group](#ldap-authentication-restricted-to-a-group)
12. [Logs](#logs)
13. [Shell Access](#shell-access)
14. [Database Operations](#database-operations)
15. [Persistent Volumes](#persistent-volumes)
16. [Update Procedure](#update-procedure)
17. [Backup Checklist](#backup-checklist)
18. [Production Considerations](#production-considerations)
19. [Troubleshooting](#troubleshooting)

---

## Prerequisites

| Requirement | Minimum version |
|---|---|
| Docker Engine | 24.x |
| Docker Compose plugin | v2.x (`docker compose`) |
| Git | any recent version |
| Free RAM | 512 MB |
| Free disk | 2 GB |

> **Note:** The legacy `docker-compose` (v1, Python) is **not** supported. Use `docker compose` (v2, Go plugin).

This guide assumes deployment through **Portainer** (Stacks → Web editor), with the
image built manually over SSH beforehand. Portainer itself runs inside its own
container and has no access to the host filesystem, so it cannot build the image —
only `docker compose up` on an already-built `deming:local` image.

---

## Architecture Overview

The stack is composed of two services orchestrated by Docker Compose:

```
                    ┌─────────────────────────────────────────────┐
        HAProxy     │              Docker network                 │
   (TLS termination)│                                             │
  Host :8000 ──────►│  nginx:80  ──► artisan serve:8000 (PHP)     │
                    │                        │                    │
                    │               ┌────────▼────────┐           │
                    │               │  mysql:3306     │           │
                    │               │  (internal only)│           │
                    │               └─────────────────┘           │
                    └─────────────────────────────────────────────┘
```

| Service | Container name | Role | Image |
|---|---|---|---|
| `deming` | `deming-app` | Nginx (reverse proxy) + Laravel (`artisan serve`) | `deming:local` — built manually, not by Compose |
| `mysql` | `deming-mysql` | Database | `mysql:9.5` |

**Important:**
- The web layer is **nginx → `php artisan serve`**, not nginx → php-fpm. Nginx listens
  on port 80 inside the container and proxies all requests to `artisan serve` on port
  8000 (internal). From the host, the application is accessible on the port mapped to
  container port 80.
- `docker-compose.yml` has **no `build:` section**. It references the pre-built image
  `deming:local` (see [Build](#build)). Fixed `container_name` values (`deming-app`,
  `deming-mysql`) are used instead of the Compose-generated defaults, to make
  `docker exec` / `docker logs` predictable.
- HAProxy (or another reverse proxy) is expected **in front of** this stack to
  terminate TLS and forward plain HTTP to the `deming` service on its published port.

---

## Quick Start

This stack expects the repository to be cloned at the **exact absolute path**
`/opt/deming` on the host — all bind-mount paths in `docker-compose.yml` are absolute
(`/opt/deming/...`), because Portainer stores the compose file in its own internal
folder, not in the cloned repository.

```bash
# 1. Clone the repository at the fixed location
mkdir -p /opt/deming
cd /opt/deming
git clone https://github.com/sourcentis/deming.git .

# 2. Create the environment file from the official template
cp .env.example .env
chmod 600 .env
nano .env
#   Set at least:
#     DB_PASSWORD  — must match MYSQL_PASSWORD in docker-compose.yml
#     APP_URL      — the PUBLIC URL via HAProxy (e.g. https://deming.example.com),
#                    NOT http://SERVER_IP:8000 nor http://localhost:8000
#     ASSET_URL    — same public URL as APP_URL

# 3. docker/custom/deming.php, Kernel.php and app.php already exist in the repo
#    cloned above — nothing to create, they are bind-mounted as-is.

# 4. Build the image manually over SSH (Portainer cannot build it itself)
cd /opt/deming
docker build -t deming:local .

# 5. Deploy: either via Portainer (Stacks → Add stack → Web editor → paste
#    docker-compose.yml → Deploy the stack), or directly over SSH:
docker compose up -d
```

The application will be available on the port mapped to the `deming` service
(**8000** by default, see [Ports](#ports)) once initialization completes
(≈ 60–90 s on first run).

---

## Environment Configuration

The `.env` file is bind-mounted from `/opt/deming/.env` into the container — it
**must exist on the host before the first startup** (otherwise Docker creates an
empty directory in its place and the app crashes). All application configuration
happens there, not in `docker-compose.yml` — with the exception of the DB connection
and a few init-time variables described below, which `docker-compose.yml` sets
directly via `environment:` so they are guaranteed to match the `mysql` service.

### Mandatory variables (kept in sync between `.env` and `docker-compose.yml`)

```dotenv
# .env — DB_PASSWORD must equal MYSQL_PASSWORD in docker-compose.yml
DB_CONNECTION=mysql
DB_HOST=mysql            # Docker service name — never 127.0.0.1
DB_PORT=3306
DB_DATABASE=deming
DB_USERNAME=deming_user
DB_PASSWORD=your_password
```

### Full `.env` reference

```dotenv
# ── Application ──────────────────────────────────────────
APP_NAME=Deming
APP_ENV=production      # actual runtime behavior (debug page, log level, etc.)
APP_KEY=                # Auto-generated on first boot if empty
APP_DEBUG=false          # true only for local troubleshooting
APP_URL=https://deming.example.com   # public URL via HAProxy
ASSET_URL=https://deming.example.com # public URL via HAProxy
APP_BANNER_TEST=false    # add a warning banner for test environments

# ── Database ─────────────────────────────────────────────
DB_CONNECTION=mysql
DB_HOST=mysql            # ← Docker service name, never 127.0.0.1
DB_PORT=3306
DB_DATABASE=deming
DB_USERNAME=deming_user
DB_PASSWORD=your_password

# ── Mail (optional) ──────────────────────────────────────
MAIL_HOST=smtp.localhost
MAIL_PORT=2525

# ── LDAP (optional) ───────────────────────────────────────
LDAP_ENABLED=false
```

> **Note on `APP_ENV`:** `docker-compose.yml` sets `APP_ENV=local` in the `deming`
> service's `environment:` block. Docker environment variables take precedence over
> `.env` values, so this **overrides** whatever `APP_ENV` is set to in `.env`, purely
> to let migrations/seeders run automatically at container startup (see
> [Environment Variables in docker-compose.yml](#environment-variables-in-docker-composeyml)).
> Keep `APP_DEBUG=false` and other production-appropriate values in `.env` regardless —
> `APP_ENV=local` here only affects the artisan bootstrap check, not debug output.

### File permissions

```bash
chmod 600 /opt/deming/.env
```

The container runs as root (no `USER` instruction in the `Dockerfile`), so it can
read/write `.env` regardless of its permissions — `chmod 600` only protects the file
against other users/processes on the host.

### MySQL root password

The image uses `MYSQL_RANDOM_ROOT_PASSWORD: "1"` — a random root password is
generated at first start and printed in the MySQL logs. The application never uses
the root account; only `MYSQL_USER` / `MYSQL_PASSWORD` (must match `DB_USERNAME` /
`DB_PASSWORD`) matter.

---

## Build

Unlike a typical Compose setup, **the image is not built by `docker compose up`** —
`docker-compose.yml` has no `build:` section, only `image: deming:local`. It must be
built manually beforehand:

```bash
cd /opt/deming
docker build -t deming:local .
```

This is required because Portainer runs inside its own container and has no access
to `/opt/deming` on the host — `docker compose build` from within Portainer would
fail with `unable to prepare context: path not found`. Build the image over SSH on
the Docker host itself, then deploy the stack from Portainer.

### What the build does

1. Starts from a Debian Bookworm + PHP 8.4 (`php:8.4-fpm-bookworm`) base image
2. Installs Nginx, required PHP extensions (`pdo_mysql`, `pdo_pgsql`, `zip`, `gd`, `ldap`, `intl`), and Composer
3. Copies the project source (build context, i.e. your local working tree) into `/var/www/deming`
4. Copies the Nginx vhost (`docker/deming.conf`) to `/etc/nginx/conf.d/deming.conf`
5. Copies initialization scripts (`entrypoint.sh`, `initialdb.sh`, `resetdb.sh`, `uploadiso27001db.sh`, `userdemo.sh`) to `/etc/`
6. Installs PHP dependencies via Composer (`composer install`)
7. Sets `EXPOSE 80` and runs `/opt/entrypoint.sh` as the container's entrypoint

### Rebuild after code or Docker script changes

```bash
docker compose down
docker build -t deming:local --no-cache .
docker compose up -d
```

> Always rebuild with `--no-cache` after any change to `Dockerfile`, `docker/entrypoint.sh`,
> `docker/initialdb.sh` or the other Docker scripts, to ensure the new version is used.

---

## Environment Variables in docker-compose.yml

These variables are set directly in the `deming` service's `environment:` block in
`docker-compose.yml` (not in `.env`), because they control container init-time
behavior or must stay in sync with the `mysql` service:

| Variable          | Values            | Description                                                          |
|--------------------|-------------------|-----------------------------------------------------------------------|
| `DB_SLEEP`         | integer (seconds) | Wait before the migration attempt, gives MySQL time to finish starting (default in this file: `10`) |
| `TZ`               | e.g. `Europe/Paris` | Container timezone — affects logs and displayed dates                |
| `DB_HOST`          | `mysql`           | Must be the Compose service name, never `127.0.0.1`                  |
| `APP_ENV`          | `local`           | **Overrides `.env`** — needed so migrations/seeders run automatically on startup |
| `DB_DATABASE`      | e.g. `deming`     | Must match `MYSQL_DATABASE` on the `mysql` service                   |
| `DB_USERNAME`      | e.g. `deming_user`| Must match `MYSQL_USER` on the `mysql` service                       |
| `DB_PASSWORD`      | —                 | Must match `MYSQL_PASSWORD` on the `mysql` service — **change before production** |
| `APP_FORCE_HTTPS`  | `false`           | Left `false`: TLS is terminated by HAProxy in front, not by Deming itself |
| `RESET_DB`         | `EN` / `FR`       | **⚠️ Wipes and recreates the entire database** — not present by default |

> **Never** add `RESET_DB` unless you intend to wipe the database — remove it again
> immediately after the reset completes. It is not part of the default
> `docker-compose.yml` shipped in this repo.

### After editing `.env`

No rebuild needed — just restart the app container:

```bash
docker restart deming-app
# or via Portainer: Containers → deming-app → Restart
```

---

## Start

### Foreground (logs visible in terminal)

```bash
docker compose up
```

### Background (detached mode — recommended, matches `restart: unless-stopped`)

```bash
docker compose up -d
```

### Check status

```bash
docker compose ps
```

Expected output when healthy:

```
NAME            IMAGE           COMMAND                SERVICE  STATUS         PORTS
deming-app      deming:local    "/opt/entrypoint.sh"   deming   Up             0.0.0.0:8000->80/tcp
deming-mysql    mysql:9.5       "docker-entrypoint…"   mysql    Up (healthy)   3306/tcp
```

The `(healthy)` status on `deming-mysql` confirms the healthcheck passed before
`deming` started — `depends_on: mysql: condition: service_healthy` in
`docker-compose.yml` enforces this ordering.

---

## Stop

### Stop containers (preserve volumes and images)

```bash
docker compose stop
```

### Stop and remove containers (preserve volumes)

```bash
docker compose down
```

### Stop, remove containers **and** volumes (⚠️ destroys all data)

```bash
docker compose down -v
```

This also removes `dbdata` and `deming_storage` — see
[Persistent Volumes](#persistent-volumes). Never run this (or check "Remove volumes"
in Portainer) without a recent backup in hand.

### Restart a single service

```bash
docker compose restart deming
```

---

## Ports

| Host port | Container port | Service | Description |
|---|---|---|---|
| **8000** | **80** | `deming` | Web application — Nginx entry point, backend target for HAProxy |
| *(internal)* | 8000 | `deming` | `artisan serve` — proxied by Nginx, not directly accessible |
| *(not published)* | 3306 | `mysql` | MySQL — declared with `expose: 3306`, internal only |

The port mapping in `docker-compose.yml`:

```yaml
services:
  deming:
    ports:
      - "8000:80"   # host:container — nginx listens on container port 80
```

This is the port HAProxy should target as its backend (plain HTTP — HAProxy handles TLS itself).

> ⚠️ **Common mistake:** `80:8000` is wrong (it maps host port 80 to container port 8000
> where nothing listens from outside). Always use `HOST_PORT:80`.

### Change the host port

```yaml
ports:
  - "80:80"     # serve on http://localhost
  - "8080:80"   # serve on http://localhost:8080
```

### MySQL is internal-only by default

`docker-compose.yml` uses `expose: [3306]` on the `mysql` service, not `ports:` — the
port is reachable from other containers on the Docker network but is **not** published
to the host. Never turn this into `ports: - "3306:3306"` in production. For local
debugging only, you may temporarily add:

```yaml
mysql:
  ports:
    - "3306:3306"
```

---

## First Connection

Once the stack is running and the logs show initialization complete, open the URL
configured in `APP_URL` (typically HAProxy's public HTTPS URL), or
`http://<server>:8000` directly for testing.

### Default credentials

| Role | Email | Password |
|---|---|---|
| Administrator | `admin@admin.localhost` | `admin` |

See [INSTALL.md](INSTALL.md) for details on how these default credentials are seeded.

> **Important:** Change the default password immediately after first login via
> **Settings → My profile → Change password**.

### Role hierarchy

| Role | Access level |
|---|---|
| Admin | Full access, user management |
| User | Controls, measures, actions |
| Auditee | Read-only on assigned controls |
| Auditor | Audit workflow access |

---

## LDAP Authentication Restricted to a Group

Deming does not support SAML natively — only LDAP, Keycloak and generic OIDC exist
(see `composer.json` / `.env.example`). To restrict LDAP authentication to members of
a specific group, add these variables to `/opt/deming/.env`:

```dotenv
LDAP_ENABLED=true
LDAP_FALLBACK_LOCAL=true        # if LDAP fails, still allow local accounts (emergency admin)
LDAP_HOST=ldap.domain.local
LDAP_PORT=389                   # 636 if LDAP_SSL=true
LDAP_USERNAME="cn=svc-deming,dc=domain,dc=local"   # service account for the bind
LDAP_PASSWORD=service_account_password
LDAP_BASE_DN="dc=domain,dc=local"
LDAP_SSL=false
LDAP_TLS=false
LDAP_LOGIN_ATTRIBUTES="uid,cn,mail,sAMAccountName,userPrincipalName"

# Restriction to a group:
LDAP_USERS_BASE_DN="ou=Users,dc=domain,dc=local"           # optional, limits the search OU
LDAP_GROUP="cn=deming-users,ou=Groups,dc=domain,dc=local"  # full DN of the allowed group
```

> **Note:** `LDAP_USERS_BASE_DN` and `LDAP_GROUP` do **not** appear in `.env.example`
> — they only exist in the code (`config/app.php` +
> `app/Http/Controllers/Auth/LoginController.php`).

The LDAP login filters on `memberOf = LDAP_GROUP`, combined with `AND` on the search
over the login attributes. Only members of this specific group can authenticate, even
if `LDAP_ENABLED=true` for the whole directory — a user outside the group is rejected
even with a valid password.

After editing `.env`, just restart the container (no rebuild needed):

```bash
docker restart deming-app
```

---

## Logs

### All services (follow mode)

```bash
docker compose logs -f
```

### Application logs only

```bash
docker compose logs -f deming
# or: docker logs -f deming-app
```

### Database logs only

```bash
docker compose logs -f mysql
# or: docker logs -f deming-mysql
```

### Laravel application log

```bash
docker compose exec deming tail -f /var/www/deming/storage/logs/laravel.log
```

### Nginx logs

```bash
docker compose exec deming tail -f /var/log/nginx/access.log
docker compose exec deming tail -f /var/log/nginx/error.log
```

### Last N lines

```bash
docker compose logs --tail=100 deming
```

### Normal startup sequence

A healthy first-run startup produces logs in this order:

```
deming-mysql  | ready for connections. Version: '9.5.0'
deming-mysql  | [Healthcheck] OK
deming-app    | Waiting for MySQL (mysql) to be ready...
deming-app    | MySQL is ready.
deming-app    | Waiting for 10 seconds before executing migration...
deming-app    | 🗄️  Running database migrations...
deming-app    |    INFO  Nothing to migrate.
deming-app    |    INFO  Seeding database.
deming-app    | 🔑 Generating Passport encryption keys...
deming-app    | 🔑 Passport installation complete
deming-app    | ✅ Deming initialization complete — starting services
```

---

## Shell Access

### Open a bash shell in the app container

```bash
docker compose exec deming bash
# or: docker exec -it deming-app bash
```

### Useful Artisan commands

```bash
# Clear all caches
docker compose exec deming php artisan cache:clear
docker compose exec deming php artisan config:clear
docker compose exec deming php artisan view:clear

# Run pending migrations
docker compose exec deming php artisan migrate --force

# Show current environment
docker compose exec deming php artisan env

# List all Artisan commands
docker compose exec deming php artisan list
```

---

## Database Operations

### Access the MySQL CLI

```bash
docker exec -it deming-mysql mysql -u deming_user -p'your_password' deming
```

### Backup the database

```bash
docker exec deming-mysql \
  mysqldump -u deming_user -p'your_password' --no-tablespaces deming \
  > backup_$(date +%Y%m%d_%H%M%S).sql
```

> `--no-tablespaces` is **required**: since MySQL 8.0.21+ (including the 9.5 used
> here), `mysqldump` reads tablespace info via `information_schema.FILES` by default,
> which requires the `PROCESS` privilege. `deming_user` is an application account, not
> root, so without this flag the dump fails with `Access denied; you need (at least
> one of) the PROCESS privilege(s)`. The flag works around this without elevating the
> user's rights.

### Restore a backup

```bash
docker exec -i deming-mysql \
  mysql -u deming_user -p'your_password' deming \
  < backup_20250101_120000.sql
```

### Full reset (⚠️ destroys all data)

Add `RESET_DB=FR` (or `EN`) to the `deming` service's `environment:` in
`docker-compose.yml`, then:

```bash
docker compose down
docker compose up -d
```

Remove `RESET_DB` from `docker-compose.yml` immediately after the reset completes.

---

## Persistent Volumes

| Volume | Container path | Contents |
|---|---|---|
| `dbdata` | `/var/lib/mysql` | All database data (controls, users, action plans...) — the most critical to back up regularly |
| `deming_storage` | `/var/www/deming/storage` | Uploaded documents, imported reference data, encryption keys, application logs |

Permissions on `deming_storage` need no manual action: `entrypoint.sh` runs a
`chown -R www-data:www-data` / `chmod -R 775` on the storage directory every time the
container starts.

### Bind mounts (host files, not Docker volumes)

These are mounted from absolute host paths and must exist **before** the first
startup:

| Host path | Container path | Notes |
|---|---|---|
| `/opt/deming/.env` | `/var/www/deming/.env` | Laravel config — create from `.env.example`, never `touch` an empty file |
| `/opt/deming/docker/custom/deming.php` | `/var/www/deming/config/deming.php` | Provided as-is by the repo, no modification |
| `/opt/deming/docker/custom/Kernel.php` | `/var/www/deming/app/Console/Kernel.php` | Provided as-is by the repo, no modification |
| `/opt/deming/docker/custom/app.php` | `/var/www/deming/config/app.php` | Provided as-is by the repo, no modification |

If a bind-mounted file is missing on the host, Docker silently creates an empty
directory in its place instead of erroring, and the app crashes on startup.

### List volumes

```bash
docker volume ls | grep deming
```

### Backup the storage volume

```bash
docker run --rm -v deming_deming_storage:/data -v "$(pwd)/backups:/backup" alpine \
  tar czf /backup/storage_$(date +%Y%m%d).tar.gz -C /data .
```

Adjust `deming_deming_storage` to the actual volume name reported by
`docker volume ls | grep deming` (Compose prefixes volume names with the project/stack
name).

---

## Update Procedure

1. **Back up first** — see [Backup Checklist](#backup-checklist) below.
2. Fetch the new version of the code:
   ```bash
   cd /opt/deming
   git pull
   ```
3. Rebuild the image manually over SSH (same as the initial install — Portainer
   cannot do it itself, see [Build](#build)):
   ```bash
   docker build -t deming:local --no-cache .
   ```
4. Restart the stack from Portainer (Stacks → deming → Stop, then Start), or over SSH:
   ```bash
   docker compose down
   docker compose up -d
   ```
   Database migrations run automatically at startup (`APP_ENV=local` in
   `docker-compose.yml` triggers Laravel migrations at boot).
5. Check the logs to confirm everything went well:
   ```bash
   docker logs -f deming-app
   ```

---

## Backup Checklist

Run this before every update, and on a regular schedule:

1. **MySQL database** (the most critical):
   ```bash
   docker exec deming-mysql mysqldump -u deming_user -p'password' --no-tablespaces deming \
     > backup_$(date +%Y%m%d).sql
   ```
2. **`deming_storage` volume** (uploaded documents, imported reference data):
   ```bash
   docker run --rm -v deming_deming_storage:/data -v "$(pwd)/backups:/backup" alpine \
     tar czf /backup/storage_$(date +%Y%m%d).tar.gz -C /data .
   ```
3. **The `/opt/deming/.env` file** (passwords, LDAP config, `APP_KEY`):
   ```bash
   cp /opt/deming/.env ~/backups/env_$(date +%Y%m%d)
   ```

`dbdata` and `deming_storage` survive a normal stop/restart of the stack, but **not**
a removal with the "Remove volumes" option checked in Portainer, or
`docker compose down -v` from the CLI — that removes the volumes and all their data.
Never do either without a recent backup in hand.

---

## Production Considerations

This `docker-compose.yml` is already written as a production/Portainer deployment, so
most hardening is baked in. Still worth checking:

### 1. Never enable `RESET_DB` outside a deliberate reset

Remove it from `docker-compose.yml` immediately after use — see
[Full reset](#full-reset--destroys-all-data).

### 2. Secure the `.env` file

```bash
chmod 600 /opt/deming/.env
```

Never commit `.env` to version control.

### 3. Keep `.env` runtime settings production-appropriate

```dotenv
APP_DEBUG=false
```

`APP_ENV=local` in `docker-compose.yml` is intentional (see
[Environment Variables in docker-compose.yml](#environment-variables-in-docker-composeyml))
and does not by itself enable debug output — that is controlled by `APP_DEBUG` in `.env`.

### 4. Automatic restarts are already enabled

```yaml
services:
  deming:
    restart: unless-stopped
  mysql:
    restart: unless-stopped
```

### 5. HTTPS is handled by HAProxy, not Deming

Place HAProxy (or another reverse proxy) in front of the stack and terminate TLS
there, forwarding plain HTTP to the `deming` service's published port. Set in `.env`:

```dotenv
APP_URL=https://deming.example.com
ASSET_URL=https://deming.example.com
APP_FORCE_HTTPS=false
```

`APP_FORCE_HTTPS` is deliberately left `false` in `docker-compose.yml` for this reason.

### 6. Keep MySQL internal

`mysql` already uses `expose: [3306]`, not `ports:`. Never publish port 3306 to the
host in production.

---

## Troubleshooting

### Build fails: "Temporary failure resolving 'deb.debian.org'"

`docker build` stops on the `apt-get update` step with exit code 100 and lines such as:

```
Failed to fetch http://deb.debian.org/debian/dists/bookworm/InRelease  Temporary failure resolving 'deb.debian.org'
```

This is a **DNS problem inside the build container**, not a certificate problem
(apt uses plain `http://` here). The host resolves names fine, but build containers
do not use the host's resolver directly:

- if the host uses a local stub resolver (`127.0.0.53` with systemd-resolved,
  `127.0.0.1` with dnsmasq...), Docker cannot reach it from the container and falls
  back to public DNS servers (`8.8.8.8`, `8.8.4.4`);
- on a corporate network, the firewall usually blocks outgoing DNS to those public
  servers, so resolution fails.

Check it:

```bash
cat /etc/resolv.conf                                   # on the host
docker run --rm debian:bookworm cat /etc/resolv.conf   # inside a container
docker run --rm debian:bookworm getent hosts deb.debian.org
```

**Fix 1 — give Docker your internal DNS servers** (recommended, permanent). Get the
real upstream servers with `resolvectl status` (or from your network team), then
create or edit `/etc/docker/daemon.json`:

```json
{
  "dns": ["10.0.0.53", "10.0.0.54"]
}
```

```bash
sudo systemctl restart docker
docker build -t deming:local .
```

**Fix 2 — build with the host network** (quick workaround, one-off):

```bash
docker build --network=host -t deming:local .
```

**If an outgoing HTTP proxy is required** on your network, pass it to the build
(these build arguments are predefined by Docker, no change to the Dockerfile is needed):

```bash
docker build \
  --build-arg http_proxy=http://proxy.example.com:3128 \
  --build-arg https_proxy=http://proxy.example.com:3128 \
  --build-arg no_proxy=localhost,127.0.0.1 \
  -t deming:local .
```

**If your firewall performs TLS inspection** (later steps such as the Composer
download fail with `SSL certificate problem: unable to get local issuer certificate`),
add your corporate root CA to the image, right after the `FROM` line of the `Dockerfile`:

```dockerfile
COPY docker/certs/*.crt /usr/local/share/ca-certificates/
RUN update-ca-certificates
```

(place your CA file(s) in PEM format with a `.crt` extension in `docker/certs/`).

### Container loops on "Not ready, retrying"

MySQL is reachable at the network level but Laravel cannot connect.
The most common cause is a wrong value in `.env`:

```bash
docker compose exec deming grep '^DB_' .env
```

Both `DB_CONNECTION` and `DB_HOST` must be set to `mysql`:

```dotenv
DB_CONNECTION=mysql   # ← not 127.0.0.1
DB_HOST=mysql         # ← not 127.0.0.1
```

Note that `DB_HOST` is also set directly in `docker-compose.yml`'s `environment:`
block, which takes precedence over `.env` — if it was edited there and mistyped, that
takes priority.

### "APPLICATION IN PRODUCTION — Command cancelled"

This means the container-level `APP_ENV=local` from `docker-compose.yml` isn't
reaching the seeders — check that the `environment:` block on the `deming` service
still has `APP_ENV=local`. Docker environment variables override `.env`, so if this
line is removed or overridden elsewhere, seeders that check `App::environment()` will
prompt for confirmation and get auto-cancelled by the non-interactive shell.

### No response on port 8000 — connection reset

The port mapping is inverted. In `docker-compose.yml`:

```yaml
ports:
  - "8000:80"   # ✅ correct  — host 8000 → container nginx 80
```

### Container starts but exits silently after cron

An initialization script exited with a non-zero code. Check:

```bash
docker logs deming-app --tail 50
```

The `|| echo "... skipped"` guards in `entrypoint.sh` (around
`uploadiso27001db.sh` and `userdemo.sh`) prevent optional scripts from killing the
startup. If a mandatory script fails (e.g. `resetdb.sh`, `initialdb.sh`), check its
output for the root cause.

### Nginx "conflicting server name" warning

Two nginx configs both declare `server_name _;`. The `Dockerfile` copies
`docker/deming.conf` to `deming.conf` (not `default.conf`), and `entrypoint.sh`
removes `/etc/nginx/sites-enabled/default` at startup. Rebuild to fix:

```bash
docker compose down
docker build -t deming:local --no-cache .
docker compose up -d
```

### sed: cannot rename — Device or resource busy

`sed -i` cannot modify `.env` from inside the container because it is a Docker bind
mount. Edit `/opt/deming/.env` on the host instead.

### Reset everything and start fresh

```bash
docker compose down -v
docker build -t deming:local --no-cache .
docker compose up -d
```

⚠️ `-v` destroys `dbdata` and `deming_storage` — back up first.

### Diagnostic commands

```bash
# Nginx config syntax check
docker compose exec deming nginx -t

# Full active nginx configuration
docker compose exec deming nginx -T

# Nginx config files present
docker compose exec deming find /etc/nginx/conf.d /etc/nginx/sites-enabled -type f

# Check PHP version and key extensions
docker compose exec deming php -v
docker compose exec deming php -m | grep -E 'pdo|mbstring|xml|gd'

# Laravel environment
docker compose exec deming php artisan env
```

