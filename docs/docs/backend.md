# Backend — Laravel 11

## Stack

| Composant | Choix | Raison |
|---|---|---|
| PHP | 8.3 | Typed properties, performance |
| Framework | Laravel 11 | Ecosystem mature |
| Serveur | Laravel Octane + Swoole | x10 perf vs PHP-FPM |
| Base de données | PostgreSQL | JSON, full-text, fiabilité |
| Cache / Queue | Redis | Sessions, queues, cache API |
| Search | Meilisearch | Full-text produits, 50Mo RAM |
| Realtime | Laravel Reverb | WebSocket sur VPS, gratuit |
| Auth | Laravel Sanctum | SPA + mobile tokens |
| Admin | Laravel Filament v3 | 0 frontend à coder |

## Packages

```bash
composer require filament/filament:"^3.0"
composer require spatie/laravel-permission
composer require spatie/laravel-data
composer require spatie/laravel-activitylog
composer require spatie/laravel-responsecache
composer require spatie/laravel-queueable-action
composer require laravel/sanctum
composer require laravel/scout
composer require laravel/horizon
composer require laravel/reverb
```

## Architecture des modules

```
app/
└── Modules/
    ├── Catalog/
    │   ├── Domain/          # Entities, ValueObjects (pas d'Eloquent)
    │   ├── Actions/         # CreateProductAction, UpdateProductAction
    │   ├── Http/            # Controllers, FormRequests
    │   ├── Events/          # ProductCreated, ProductApproved
    │   ├── Listeners/
    │   ├── Models/          # Eloquent models (adapter)
    │   └── Filament/        # Resources Filament admin
    ├── Order/
    ├── Payment/
    ├── User/
    ├── Delivery/
    └── Notification/
```

**Règle stricte :** Le Domain ne dépend d'aucune classe Laravel. Un module n'accède jamais directement à la DB d'un autre module.

## Logique métier par module

### Module Catalog
- `CreateProductAction` → valide vendeur `verified`, calcule slug unique, status `pending`
- Event `ProductCreated` → indexe dans Meilisearch
- Event `ProductApproved` → notifie vendeur

### Module Order
- `CreateOrderAction` → vérifie stock disponible, calcule total + frais livraison par zone, crée Order `pending`
- Event `OrderCreated` → réserve stock (decrement `reserved_stock`)

### Module Payment
- `InitiatePaymentAction` → appelle CinetPay API, stocke `transaction_id`, retourne URL paiement
- Webhook `POST /api/webhooks/cinetpay` → `HandlePaymentWebhookAction`
  - Si `ACCEPTED` → émet `OrderPaid`
  - Si `REFUSED` → émet `OrderPaymentFailed`, libère stock réservé
- Event `OrderPaid` → Order passe en `paid`, argent en escrow

### Module Delivery
- Admin assigne livreur manuellement (MVP)
- `AcceptDeliveryAction` → livreur accepte, Order → `picked`
- `CompleteDeliveryAction` → vérifie OTP client, Order → `completed`, émet `DeliveryCompleted`
- Event `DeliveryCompleted` → déclenche calcul payout vendeur 48h (job différé)

### Module Notification
Écoute tous les events et envoie :
- SMS via CinetPay SMS API
- Email via Laravel Mail + queue
- Push via Reverb

### Module User
- `VerifySellerAction` → admin valide KYC, vendor passe en `verified`, émet `SellerVerified`
- `SuspendUserAction` → suspend compte + invalide tokens Sanctum

## Filament Admin Resources

| Resource | Actions custom |
|---|---|
| `UserResource` | Vérifier vendeur, Suspendre, Changer rôle |
| `ProductResource` | Approuver, Rejeter avec motif |
| `OrderResource` | Forcer statut, Initier remboursement |
| `DisputeResource` | Décision admin, Rembourser partiel/total |
| `PayoutResource` | Lancer virement CinetPay Payout API |

## Performance

- `spatie/laravel-responsecache` sur toutes les routes GET publiques (TTL 5min)
- Redis cache sur `GET /api/products`, `GET /api/categories`
- Meilisearch pour recherche produits (pas de `ILIKE` SQL en prod)
- Octane keeps workers en mémoire : cible 1500 req/s sur `/api/products`

## Commandes importantes

```bash
# Démarrer
php artisan octane:start --server=swoole --workers=4

# Queues
php artisan queue:work --sleep=3 --tries=3 --timeout=90

# Horizon (monitoring queues)
php artisan horizon

# Reverb (WebSocket)
php artisan reverb:start

# Scout — indexer produits
php artisan scout:import "App\Modules\Catalog\Models\Product"
```
