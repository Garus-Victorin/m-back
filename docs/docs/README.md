# Marketify — Documentation Complète

Marketplace e-commerce multi-acteurs pour le marché béninois.

## Apps

| App | Rôle | Stack | Cible |
|---|---|---|---|
| **Marketify Admin** | Piloter la plateforme | Laravel Filament v3 | Desktop |
| **Marketify Sell** | Vendeurs — gérer boutique | Angular 19 + Material 3 + PWA | Desktop / Tablette |
| **Marketify Shop** | Clients — acheter | Angular 19 + Tailwind + PWA + Capacitor | Mobile first |
| **Marketify Go** | Livreurs — livrer | Angular 19 + Capacitor (APK natif) | Mobile uniquement |

## Flux principal (escrow end-to-end)

```
Client paie (Shop)
  → statut: pending → paid (argent bloqué)
    → Vendeur prépare (Sell)
      → statut: ready
        → Livreur récupère (Go) → scan QR
          → statut: picked
            → Livreur livre → OTP client
              → statut: completed
                → Cron paie vendeur (- commission) 48h après
                  → Si litige → Admin intervient (Admin)
```

## Index de la documentation

- [Architecture globale](./architecture.md)
- [Backend — Laravel 11](./backend.md)
- [API — Routes & Contrats](./api.md)
- [Frontend — Vue d'ensemble](./frontend.md)
- [App Admin](./app-admin.md)
- [App Sell (Vendeur)](./app-sell.md)
- [App Shop (Client)](./app-shop.md)
- [App Go (Livreur)](./app-go.md)
- [Paiement & Escrow](./payment.md)
- [Base de données](./database.md)
- [Déploiement](./deployment.md)
- [MVP — Ce qu'on coupe](./mvp-scope.md)
