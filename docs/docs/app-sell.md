# App Sell — Marketify Sell (Vendeur)

**Stack :** Angular 19 + Angular Material 3 + PWA
**Cible :** Desktop et tablette (vendeur sur WiFi / 4G)
**Bundle max :** 1 MB gzippé

## Routes

```
/login                  # Auth
/onboarding             # Création boutique (multi-step)
/dashboard              # Vue principale
/products               # Liste produits
/products/new           # Créer produit
/products/:id/edit      # Modifier produit
/orders                 # Commandes reçues
/orders/:id             # Détail commande
/chat                   # Conversations clients
/finance                # Solde + historique
/settings               # Paramètres boutique
```

## Composants principaux

### Onboarding (mat-stepper)

4 étapes :
1. Infos boutique (nom, catégorie, description)
2. Upload logo + bannière
3. KYC (pièce identité, numéro de téléphone Mobile Money)
4. Confirmation → status `pending` → message "En attente de validation"

### Dashboard

```
┌─────────────┬─────────────┬─────────────┐
│  CA du jour │  Commandes  │  En rupture │
│   45 000 F  │      3      │      2      │
└─────────────┴─────────────┴─────────────┘
[Graphique CA 7 derniers jours — ng-apexcharts]
[Liste commandes récentes]
```

### Gestion Produits

- `mat-table` avec tri + filtre + pagination serveur
- Dialog création/modification :
  - Upload multiple images (drag & drop, preview, compression côté client)
  - Variantes taille/couleur (chips dynamiques)
  - Prix, stock, description, catégorie
  - Programmer une promo (date début/fin + %)

### Gestion Commandes

- Liste `mat-table` avec filtre par statut
- Détail : slide-over `mat-sidenav`
  - Timeline statut commande
  - Bouton "Prêt pour enlèvement" → PATCH `/seller/orders/:id/ready`
  - Bouton "Imprimer bon"
  - Chat client intégré

### Finance

- Cartes : Solde disponible, Commissions prélevées, Total gagné
- Tableau historique virements
- Bouton "Demander retrait" → dialog montant + sélection Mobile Money

### PWA Offline

Service Worker cache :
- `GET /seller/orders?status=pending` (TTL 5 min)
- Vue lecture seule si hors réseau

## State (Angular Signals)

```typescript
// services/seller.store.ts
export class SellerStore {
  orders = signal<Order[]>([]);
  pendingCount = computed(() => this.orders().filter(o => o.status === 'ready').length);
}
```

## Formulaire produit (Reactive Forms)

```typescript
productForm = this.fb.group({
  title: ['', [Validators.required, Validators.minLength(5)]],
  price: [null, [Validators.required, Validators.min(1)]],
  stock: [null, [Validators.required, Validators.min(0)]],
  category_id: [null, Validators.required],
  description: [''],
  variants: this.fb.array([])
});
```
