# Frontend — Vue d'ensemble

## Stack commune aux 4 apps

| Outil | Version | Rôle |
|---|---|---|
| Angular | 19 | Framework (Standalone Components, pas de NgModule) |
| Angular Material | 3 | Design system Admin + Sell + Go |
| Tailwind CSS | 3 | Layout + styles Shop (Material trop lourd pour vitrine) |
| Angular CDK | 19 | Overlay, virtual scroll, portal |
| Capacitor | 6 | APK natif pour Go (GPS, caméra, push) |
| @angular/pwa | 19 | Service Worker, installable |
| RxJS + Signals | — | State management (pas de NgRx MVP) |
| lucide-angular | latest | Icônes légères |
| ngx-translate | latest | i18n (FR uniquement MVP, structure prête) |

## Structure monorepo (recommandé)

```
marketify/
├── apps/
│   ├── admin/       # Filament → pas d'Angular
│   ├── sell/        # Angular 19 + Material
│   ├── shop/        # Angular 19 + Tailwind
│   └── go/          # Angular 19 + Capacitor
└── libs/
    └── shared/
        ├── models/      # Interfaces TS (Order, Product, User...)
        ├── services/    # ApiService, AuthService communs
        ├── guards/      # authGuard, roleGuard
        ├── interceptors/ # TokenInterceptor, LoadingInterceptor
        └── pipes/       # CurrencyBJPipe, TimestampPipe
```

Utilise Nx ou Angular Workspace multi-project.

## Setup d'une app

```bash
ng new marketify-sell --standalone --routing --style=scss
ng add @angular/material
ng add @angular/pwa
npx cap add android
```

## Patterns communs

### Auth interceptor (toutes les apps)

```typescript
export const tokenInterceptor: HttpInterceptorFn = (req, next) => {
  const token = inject(AuthService).token();
  if (!token) return next(req);
  return next(req.clone({ setHeaders: { Authorization: `Bearer ${token}` } }));
};
```

### Guards de route

```typescript
export const authGuard: CanActivateFn = () => {
  const auth = inject(AuthService);
  return auth.isLoggedIn() ? true : inject(Router).createUrlTree(['/login']);
};

export const roleGuard = (role: string): CanActivateFn => () => {
  const auth = inject(AuthService);
  return auth.hasRole(role) ? true : inject(Router).createUrlTree(['/forbidden']);
};
```

### Lazy loading routes (toutes les apps)

```typescript
export const routes: Routes = [
  { path: 'products', loadComponent: () => import('./products/products.component') },
  { path: 'orders', canActivate: [authGuard], loadComponent: () => import('./orders/orders.component') },
];
```

## Performance budgets

| App | Bundle max gzippé | Contexte |
|---|---|---|
| Admin | 1.5 MB | WiFi bureau |
| Sell | 1 MB | WiFi / 4G vendeur |
| Shop | **600 KB** | 3G mobile client |
| Go | 800 KB | 4G terrain |

## PWA — Service Worker

Chaque app a son `ngsw-config.json`. Stratégie :
- **Shop** : cache agressif produits vus + page accueil offline
- **Sell** : cache commandes du jour
- **Go** : cache adresses + courses en cours

```json
{
  "dataGroups": [
    {
      "name": "api-products",
      "urls": ["/api/v1/products"],
      "cacheConfig": { "strategy": "freshness", "timeout": "3s", "maxAge": "1h" }
    }
  ]
}
```

## Ce qu'on ne fait pas pour le MVP

- ❌ SSR / Angular Universal (ralentit le dev)
- ❌ NgRx Store (trop lourd, Signals suffisent)
- ❌ WebSockets (polling 10s sur commandes)
- ❌ Tests E2E (seulement tests unitaires critiques)
- ❌ Multi-langue (FR uniquement, structure i18n prête)
