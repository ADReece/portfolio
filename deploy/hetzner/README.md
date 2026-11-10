# Portfolio on the Hetzner server (production)

Production runs at **https://portfolio.ascend-digital.co.uk** on the Hetzner VPS
(Coolify v4, Traefik). The Raspberry Pi (`hydrogen-deploy`, root `Dockerfile` and
`hydrogen.json`) stays as **staging**; nothing here changes it.

## How a release reaches the server

1. Push a version tag: `git tag v1.2.3 && git push origin v1.2.3`, or run the
   **Release** workflow by hand from the Actions tab.
2. `.github/workflows/release.yml` runs the syntax check and PHP tests, builds
   `deploy/hetzner/Dockerfile` for `linux/amd64` and pushes
   `ghcr.io/adreece/portfolio` with three tags:
   - `prod`: the moving tag the server watches
   - the version tag, e.g. `v1.2.3` (tag pushes only)
   - `sha-<short commit>`
3. A timer on the server sees `:prod` change and redeploys through Coolify
   within about a minute. CI never connects to the server and needs no
   secrets beyond `GITHUB_TOKEN`.

To roll back, re-point `prod` at an earlier image (for example re-run the
workflow on the earlier tag) or pin the compose `image:` to `sha-<short>` in
Coolify.

## Services

All three use the same image; the command picks the role.

| Service | Command | Port | Health check | Memory |
|---|---|---|---|---|
| `web` | `web`: nginx + PHP-FPM | 8080 (internal; Traefik routes to it) | `GET /up` returns 200 | 176m |
| `queue` | `queue`: `php artisan queue:work` | – | `queue:work` process running | 160m |
| `scheduler` | `scheduler`: `php artisan schedule:work` | – | `schedule:work` process running | 64m |

Total is 400 MB. The queue resizes uploaded photos with Imagick, so if very
large photos fail to process, raise the queue's `mem_limit` first.

- The containers run as the non-root user `app` (uid 1000).
- The scheduler and queue worker are compose services, not host cron. The
  scheduler runs the Prodigi catalogue sync daily and the order sync every
  30 minutes, so it needs the Prodigi keys.
- Logs go to stderr (`LOG_CHANNEL=stderr`); nothing is written to
  `storage/logs`. Read them in Coolify.

## Configuration

Every variable is listed in [`.env.example`](.env.example), marked
`[GENERATE]`, `[CARRY OVER]` or `[CHOOSE]`. Set them in Coolify. The image
contains no secrets.

- **Database:** Coolify's managed MariaDB/MySQL. Set `DB_HOST`, `DB_PORT`,
  `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD`. The app bundles no database.
- **Redis:** not needed. The queue and sessions use the database, and the
  cache uses files.
- **Uploads:** the `storage` named volume at `storage/app`, shared by web
  (which receives uploads) and queue (which processes them). Setting the
  `AWS_*` variables with a bucket switches to S3, as the old server used.
- **Proxy:** the app trusts `X-Forwarded-*` from any proxy, so URLs and
  secure cookies are https behind Traefik. Don't publish port 8080 on the host.

## Commands

The web container runs these automatically on start:

- `php artisan migrate --force`, unless `RUN_MIGRATIONS=false`. The queue and
  scheduler wait for the web service to be healthy, so they start after
  migrations.
- `config:cache`, `route:cache`, `view:cache` and `event:cache` (every service).

`storage:link` is built into the image (`public/storage` points to
`storage/app/public`), so there's no need to run it.

One-off commands, from Coolify's terminal on the `web` service or with
`docker compose run --rm web <command>`:

```bash
php artisan migrate --force          # if RUN_MIGRATIONS=false
php artisan db:seed --class=SubscriptionPlanSeeder --force   # default plans, first install only
php artisan key:generate --show      # print a new APP_KEY
php artisan queue:failed             # inspect failed jobs
```

## Moving from dev2.ascend-digital.co.uk

The old server ran Laravel 9 on PHP 8.0. This image is Laravel 12 on PHP 8.3.

1. Back up the old database and uploaded files.
2. Restore the database into Coolify's MariaDB and run the migrations once on a
   copy first. Two migrations change existing tables: one renames
   `subscriptions.name` to `type` for Cashier 15, and one adds a column to `users`.
   A third creates the `sessions` table.
3. Files: if dev2 used S3, set the same `AWS_*` values. If it stored files
   locally, copy `storage/app` into the `storage` volume.
4. Keep the old `APP_KEY` so existing encrypted data and links still work.
5. In Stripe, add a webhook endpoint for
   `https://portfolio.ascend-digital.co.uk/stripe/webhook` and set
   `STRIPE_WEBHOOK_SECRET`. Update the Stripe Connect redirect URL to
   `https://portfolio.ascend-digital.co.uk/connect/stripe/callback`.
