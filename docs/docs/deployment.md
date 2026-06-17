# Déploiement — Marketify

## Infrastructure cible

**VPS Hetzner CX22** (ou Oracle Cloud Free Tier pour MVP)
- 2 vCPU / 4 Go RAM / 40 Go SSD
- Ubuntu 22.04 LTS
- Coût : ~7€/mois Hetzner, gratuit Oracle

## Architecture Docker

```yaml
# docker-compose.yml
services:
  nginx:
    image: nginx:alpine
    ports: ["80:80", "443:443"]
    
  app:
    build: .  # PHP 8.3 + Swoole
    command: php artisan octane:start --server=swoole --host=0.0.0.0 --port=8000 --workers=4
    
  queue:
    build: .
    command: php artisan queue:work --sleep=3 --tries=3 --timeout=90
    
  reverb:
    build: .
    command: php artisan reverb:start --host=0.0.0.0 --port=8080
    
  postgres:
    image: postgres:16-alpine
    environment:
      POSTGRES_DB: marketify
      POSTGRES_USER: marketify
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes:
      - postgres_data:/var/lib/postgresql/data
      
  redis:
    image: redis:7-alpine
    
  meilisearch:
    image: getmeili/meilisearch:latest
    volumes:
      - meili_data:/meili_data

volumes:
  postgres_data:
  meili_data:
```

## Nginx config (extrait)

```nginx
server {
    listen 443 ssl;
    server_name api.marketify.bj;
    
    location / {
        proxy_pass http://app:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
    
    location /app/ {
        proxy_pass http://reverb:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }
}
```

## Variables d'environnement (.env production)

```env
APP_ENV=production
APP_KEY=base64:...

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_DATABASE=marketify
DB_USERNAME=marketify
DB_PASSWORD=

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=redis

MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=

CINETPAY_API_KEY=
CINETPAY_SITE_ID=
CINETPAY_SECRET=
CINETPAY_BASE_URL=https://api-checkout.cinetpay.com/v2

REVERB_APP_ID=marketify
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST=ws.marketify.bj
```

## Supervisor (keep-alive Octane + Queue)

```ini
# /etc/supervisor/conf.d/marketify.conf
[program:octane]
command=php /var/www/artisan octane:start --server=swoole --workers=4
autostart=true
autorestart=true

[program:queue]
command=php /var/www/artisan queue:work --sleep=3 --tries=3
numprocs=2
autostart=true
autorestart=true

[program:horizon]
command=php /var/www/artisan horizon
autostart=true
autorestart=true
```

## CI/CD (GitHub Actions)

```yaml
# .github/workflows/deploy.yml
on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Deploy via SSH
        run: |
          ssh ${{ secrets.VPS_USER }}@${{ secrets.VPS_IP }} << 'EOF'
            cd /var/www/marketify
            git pull origin main
            composer install --no-dev --optimize-autoloader
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            php artisan octane:reload
          EOF
```

## Frontends — Déploiement

Les 4 frontends Angular sont des SPAs statiques :

```bash
# Build
ng build --configuration=production --base-href=/

# Upload sur CDN (Cloudflare Pages gratuit ou S3 + CloudFront)
# Ou servir via Nginx sur le même VPS dans /var/www/html/
```

| App | URL |
|---|---|
| Admin (Filament) | `https://admin.marketify.bj` (servi par Laravel) |
| Sell | `https://sell.marketify.bj` |
| Shop | `https://marketify.bj` |
| Go | APK téléchargé directement |

## Cron Laravel

```php
// app/Console/Kernel.php
$schedule->job(PayoutSellersJob::class)->dailyAt('02:00');
$schedule->job(CleanExpiredCartsJob::class)->hourly();
$schedule->job(AutoCompleteOrdersJob::class)->everyFifteenMinutes();
```

## Cibles de performance

| Métrique | Cible |
|---|---|
| API `/products` | 1500 req/s (avec Redis cache) |
| Time to first byte | < 200ms |
| Shop LCP (3G) | < 2.5s |
| Uptime | 99.5% |
