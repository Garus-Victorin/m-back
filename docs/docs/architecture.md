# Architecture Globale — Marketify

## Vue d'ensemble

Marketify est un **Modular Monolith** Laravel avec 4 frontends indépendants communiquant tous via la même API REST.

```
┌─────────────────────────────────────────────────────────┐
│                    FRONTENDS                            │
│                                                         │
│  marketify-admin    marketify-sell    marketify-shop    │
│  (Filament v3)      (Angular 19)      (Angular 19)      │
│                                                         │
│                    marketify-go                         │
│                    (Angular 19 + Capacitor APK)         │
└────────────────────────┬────────────────────────────────┘
                         │ HTTPS REST API
┌────────────────────────▼────────────────────────────────┐
│              Laravel 11 — API Backend                   │
│                                                         │
│  /Modules/Catalog   /Modules/Order   /Modules/Payment   │
│  /Modules/User      /Modules/Notification               │
│                                                         │
│  Auth: Laravel Sanctum                                  │
│  Queue: Redis + Laravel Horizon                         │
│  Realtime: Laravel Reverb (WebSocket)                   │
└──────┬────────────────┬──────────────────┬──────────────┘
       │                │                  │
  PostgreSQL          Redis            Meilisearch
```

## Décisions d'architecture

### Pourquoi Modular Monolith ?
- Un seul repo, un seul déploiement pour le MVP
- Modules découplés via Events → migration microservices facile en V2
- Pas de surcoût DevOps

### Pourquoi Angular 19 partout (et non Flutter pour Shop/Go) ?
- Une seule équipe frontend, un seul écosystème
- Capacitor wrap en APK natif pour Go (GPS background, caméra)
- PWA pour Shop : installable sans App Store
- Flutter écarté pour MVP : double compétence Flutter + Angular = risque planning

### Communication inter-modules
Les modules ne s'appellent **jamais directement**. Ils communiquent via Laravel Events.

```
PaymentModule → émet OrderPaid
  → OrderModule écoute → libère stock
  → NotificationModule écoute → SMS/push client + vendeur
  → CommissionModule écoute → calcule commission vendeur
```

## Statuts d'une commande

```
pending → paid → ready → picked → in_delivery → completed
                                               → disputed → resolved
                        ↘ cancelled (avant paid uniquement)
```

## Rôles & permissions (Spatie Permission)

| Rôle | Accès |
|---|---|
| `admin` | Tout |
| `moderator` | Produits, litiges uniquement |
| `finance` | Finance, virements uniquement |
| `seller` | Sa boutique uniquement |
| `customer` | Ses commandes uniquement |
| `driver` | Ses courses uniquement |
