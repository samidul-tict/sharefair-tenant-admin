# Docker Deployment (CI via GitHub Actions)

This app ships as a single Docker image (Nginx + PHP-FPM + Supervisor) built by
GitHub Actions and pushed to **GitHub Container Registry (GHCR)**. Deployment to
your server is **manual** — pull the image and run `docker compose up -d`.
PostgreSQL is **external** (managed/separate host).

## Flow

```
push to main ──▶ GitHub Actions
                   ├─ build multi-stage Docker image (Vite assets + Composer + PHP runtime)
                   └─ push to ghcr.io/<owner>/<repo>:latest and :sha-xxxxxxx

manual on server ──▶ docker compose pull ▶ up -d ▶ migrate (entrypoint)
```

## What runs inside the container

- **nginx** on port 80 → serves `public/`, proxies PHP to php-fpm
- **php-fpm** (PHP 8.2, `pdo_pgsql`, `opcache`, `gd`, `intl`, `bcmath`, `zip`)
- **queue worker** (`php artisan queue:work`) — because `QUEUE_CONNECTION=database`
- **scheduler** (`php artisan schedule:run` every 60s)
- On startup the **entrypoint** runs `migrate --force` and caches config/routes/views.

---

## 1. One-time server setup

On your VPS (Docker + Docker Compose plugin installed):

```bash
mkdir -p /opt/sharefair && cd /opt/sharefair

# Copy the compose file from the repo (or scp it once).
curl -O https://raw.githubusercontent.com/<owner>/<repo>/main/docker-compose.yml

# Create the production .env (see .env.production.example in the repo).
nano .env
```

Minimum `.env` on the server (used by both compose and the app):

```env
APP_IMAGE=ghcr.io/<owner>/<repo>:latest
APP_PORT=8080

APP_NAME="Share Fair"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...        # generate once: docker run --rm ghcr.io/<owner>/<repo>:latest php artisan key:generate --show
APP_URL=https://sharefair.life
ASSET_URL=https://sharefair.life

DB_CONNECTION=pgsql
DB_HOST=your-postgres-host
DB_PORT=5432
DB_DATABASE=sharefair
DB_USERNAME=sharefair
DB_PASSWORD=super-secret

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp
EMAIL_HOST=smtp.gmail.com
EMAIL_PORT=587
EMAIL_USER=...
EMAIL_PASS=...
```

> **APP_KEY is required.** Generate it once and paste it into `.env`. Without a
> stable key, sessions/encrypted data break on every restart.

Put a reverse proxy (Nginx/Caddy/Traefik) in front for TLS, forwarding 443 →
`APP_PORT` (8080). The app already forces HTTPS when `APP_ENV=production`.

---

## 2. GitHub repository secrets

No deploy secrets are required for CI. `GITHUB_TOKEN` is provided automatically by
Actions and is used to push to GHCR.

If the GHCR package is **private**, your server needs a PAT with `read:packages`
to pull the image:

```bash
echo "<PAT>" | docker login ghcr.io -u <github-username> --password-stdin
```

---

## 3. Deploy (manual)

After a push to `main`, wait for the **Docker CI** workflow to finish, then on
the server:

```bash
cd /opt/sharefair   # or your DEPLOY_PATH
docker compose pull
docker compose up -d --remove-orphans
```

You can pin a specific build with the commit tag, e.g.
`ghcr.io/samidul-tict/sharefair-tenant-admin:sha-abc1234`.

You can also trigger a new image build manually from the **Actions** tab
(workflow_dispatch).

---

## Local testing

Build and run the production image locally against a local Postgres:

```bash
docker build -t sharefair:local .
docker run --rm -p 8080:80 --env-file .env sharefair:local
# open http://localhost:8080
```

## Useful commands on the server

```bash
docker compose logs -f app          # tail logs (nginx, php-fpm, queue, scheduler)
docker compose exec app php artisan migrate:status
docker compose exec app php artisan tinker
docker compose pull && docker compose up -d   # manual redeploy
```

## Notes

- **Migrations** run automatically on container start. To disable, set
  `RUN_MIGRATIONS=false` in the server `.env`.
- **Uploaded files** persist in the `storage-data` Docker volume. If you rely on
  `public/storage`, the entrypoint runs `storage:link` on boot.
- For **zero-downtime**, put the app behind a proxy and scale with a second
  container/tag before switching — out of scope for this simple setup.
