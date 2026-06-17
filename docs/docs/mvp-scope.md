# MVP — Périmètre et Coupes

## Ce qu'on livre pour le MVP

### ✅ Inclus

**Backend**
- Auth complète (login, register, rôles)
- CRUD produits avec validation admin
- Flux commande complet (pending → completed)
- Paiement CinetPay (MTN MoMo + Moov Money)
- Escrow simplifié (balance vendeur en base)
- Virement manuel via Filament (pas d'auto-payout)
- Chat simple (REST, pas temps réel)
- GPS livreur (envoi position toutes les 5s)
- Notifications SMS via CinetPay SMS API
- Filament Admin opérationnel

**Frontend**
- Shop : catalogue, fiche produit, panier, checkout, suivi
- Sell : dashboard, gestion produits, commandes, finance
- Go : liste courses, workflow livraison, gains
- PWA installable sur Shop et Sell
- APK Go via Capacitor

---

## ❌ Coupé pour le MVP (prévu V2)

| Fonctionnalité | Raison coupée | V2 |
|---|---|---|
| WebSockets (Reverb) | Polling 10s suffit | V2 — chat temps réel, notifs live |
| Meilisearch en prod | Recherche SQL ILIKE pour MVP | V2 — full-text performant |
| Auto-assignation livreur | Admin assigne manuellement | V2 — algo géographique |
| Payout automatique | Admin lance manuellement | V2 — cron CinetPay Payout |
| Litiges complexes | Bouton "Annuler" avant livraison | V2 — chat tripartite + médiation |
| SSR Angular | Ralentit le dev | V2 si SEO nécessaire |
| i18n multi-langue | FR uniquement | V2 — Fon, EN |
| Tests E2E | Manque de temps | V2 — Cypress |
| Escrow réel (fonds tiers) | Complexité légale | V2 — compte séquestre |
| Programme fidélité | Non critique | V2 |
| Codes promo | Non critique | V2 |

---

## Sprints MVP (5 semaines)

### Sprint 1 — Semaine 1-2 : Backend fondations
- [ ] Setup Laravel 11, Octane, Sanctum, Filament
- [ ] Migrations DB complètes
- [ ] Module User (auth, rôles, KYC)
- [ ] Module Catalog (CRUD produits, validation admin)
- [ ] Module Order (créer commande, statuts)
- [ ] Routes API `/auth`, `/products`, `/orders`

### Sprint 2 — Semaine 2-3 : Paiement + Livraison
- [ ] Intégration CinetPay (paiement + webhook)
- [ ] Escrow en base (balance vendeur)
- [ ] Module Delivery (GPS livreur, workflow QR + OTP)
- [ ] Chat REST simple
- [ ] SMS notifications

### Sprint 3 — Semaine 3 : Admin Filament
- [ ] UserResource (validation KYC)
- [ ] ProductResource (modération)
- [ ] OrderResource (vue globale)
- [ ] Finance (soldes, lancer virements manuels)
- [ ] Analytics dashboard basique

### Sprint 4 — Semaine 4 : Frontend Shop + Sell
- [ ] marketify-shop : accueil, produit, panier, checkout, suivi
- [ ] marketify-sell : dashboard, produits, commandes, finance
- [ ] Auth guards + interceptors
- [ ] PWA config Service Worker

### Sprint 5 — Semaine 5 : App Go + Polish
- [ ] marketify-go : courses, workflow, carte
- [ ] Capacitor build APK
- [ ] Tests manuels end-to-end du flux complet
- [ ] Fix bugs, polish UI
- [ ] Déploiement VPS

---

## Critères de succès MVP

- [ ] Un client peut s'inscrire, trouver un produit, payer en MTN MoMo
- [ ] Un vendeur reçoit la commande et la marque prête
- [ ] Un livreur accepte, scanne QR, livre avec OTP
- [ ] L'admin voit la transaction, peut lancer le virement
- [ ] Le flux complet prend < 5 min en démo
