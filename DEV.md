# Local development (no rebuild on every change)

## Why you had to rebuild

The production Docker image **bakes in** your code and runs `npm run build` once.  
Changing PHP or React without a dev setup means `docker compose build app` again.

Also, if `public/hot` exists but **Vite is not running**, pages can look **dark / frozen / blocked**. That file is created by `npm run dev`. In `local`, the app now deletes a stale `public/hot` automatically on each request when Vite is unreachable.

## Quick fix for dark / blocked UI right now

```bash
npm run dev:stop
docker compose exec app php artisan optimize:clear
```

Then hard-refresh the browser (`Ctrl+Shift+R`).

- **Admin:** `http://localhost:8888/admin-backoffice-free-dom` (not `/admin` — old URL redirects).
- **Storefront:** needs `npm run dev` **or** a production `npm run build`.
- If the admin panel looks **dimmed / blocked** (dark veil over the table):
  1. Open DevTools → Application → Local Storage → `http://localhost:8888` → **Clear all** (old `isOpen` / `theme` keys cause this).
  2. Hard-refresh (`Ctrl+Shift+R`).
  3. Admin now uses **top navigation** (no sidebar overlay). If it still blocks, try an incognito window.
- If the admin form felt stuck after editing the product name, refresh once (Livewire full-form reload on blur was removed).

## Recommended workflow (Docker + watch)

**Terminal 1 — stack with live PHP/Filament sync (no rebuild per change):**

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
```

**Terminal 2 — frontend hot reload (only when editing `resources/js` or CSS):**

```bash
npm run dev
```

- **Admin / Compras / Ventas:** save PHP → refresh browser. No `npm run dev` required.
- **Storefront:** keep `npm run dev` running for instant CSS/JS updates.

**First time only** (or after `composer.json` / `package.json` changes):

```bash
docker compose build app
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
```

## Without Docker (XAMPP)

```bash
php artisan serve
npm run dev
```

Use `.env` with `DB_HOST=127.0.0.1` and `DB_PORT=33060` if MySQL is in Docker.

## When you DO need a production build

Only when deploying or testing built assets without Vite:

```bash
npm run build
docker compose build app
docker compose up -d
```
