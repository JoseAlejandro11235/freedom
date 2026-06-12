# Production Deployment

This project is ready to run on a dedicated Hetzner server with Docker Compose and Caddy.

## 1. Prepare The Server

```bash
apt update && apt upgrade -y
apt install -y git docker.io docker-compose-plugin
systemctl enable --now docker
```

Point your domain `A` record to the Hetzner server IP.

## 2. Clone The Project

```bash
cd /var/www
git clone YOUR_REPO_URL freedom
cd freedom
```

## 3. Configure Environment

```bash
cp .env.production.example .env
nano .env
```

Set at least:

```env
APP_DOMAIN=yourdomain.com
DB_PASSWORD=strong-db-password
MINIO_ROOT_USER=strong-minio-user
MINIO_ROOT_PASSWORD=strong-minio-password
ADMIN_EMAIL=admin@yourdomain.com
```

Generate the app key:

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml run --rm --entrypoint php app artisan key:generate --show
```

Copy the generated value into `APP_KEY` in `.env`.

## 4. Start Production

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

Create the MinIO bucket once:

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml --profile setup up minio-init
```

Run migrations:

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan migrate --force
```

## 5. Useful Commands

View logs:

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml logs -f
```

Restart:

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml restart
```

Deploy updates:

```bash
git pull
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec app php artisan migrate --force
```

## Important

Do not run `docker system prune -a --volumes` after production data exists. It can delete the MySQL and MinIO volumes.
