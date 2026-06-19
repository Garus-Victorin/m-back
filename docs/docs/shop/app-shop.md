# App Shop — Marketify Shop (Client)

**Stack :** Angular 19 + Tailwind CSS + Angular CDK + PWA + Capacitor
**Cible :** Mobile first (3G béninoise)
**Bundle max :** 600 KB gzippé

> Material est écarté ici. Trop lourd pour une vitrine client. Angular CDK + Tailwind = même UX, 3× plus léger.

## Routes

```
/                       # Accueil
/search                 # Recherche + filtres
/category/:slug         # Produits par catégorie
/shop/:id               # Vitrine vendeur
/product/:id            # Fiche produit
/cart                   # Panier
/checkout               # Tunnel d'achat (3 steps)
/checkout/payment       # Paiement CinetPay
/orders                 # Historique commandes
/orders/:id             # Suivi commande
/orders/:id/dispute     # Ouvrir litige
/profile                # Compte utilisateur
/login                  # Auth
/register               # Inscription
```

## Pages clés

### Accueil `/`

```
[Bannière promotionnelle — carousel léger]
[Catégories — grille 4 colonnes avec icônes]
[Produits populaires — SliverList virtual scroll]
[Vendeurs mis en avant]
```

- Chargement initial : données en cache Service Worker si disponibles
- Images en format WebP, `loading="lazy"`, `ng-optimized-image`

### Fiche produit `/product/:id`

- Galerie photos (swipe mobile)
- Prix, stock en temps réel
- Sélecteur variantes taille/couleur
- Bouton "Ajouter au panier"
- Note + avis clients (lazy loaded)
- Profil vendeur avec lien boutique

### Panier `/cart`

- Persiste dans `localStorage` (offline)
- Synchro avec API au login
- Récap avec sous-total, frais livraison estimés

### Checkout `/checkout`

3 étapes via stepper CDK :

1. **Adresse** : sélectionner adresse sauvegardée ou nouvelle, saisir ville/quartier
2. **Livraison** : domicile (frais calculés par zone) ou point relais
3. **Paiement** : CinetPay widget intégré (MTN MoMo, Moov Money, carte)

### Suivi commande `/orders/:id`

- Polling API toutes les 10s (MVP — pas de WebSocket)
- Timeline visuelle des statuts
- Chat vendeur + livreur (quand assigné)
- Bouton "Confirmer réception" → dialog saisie OTP
- Bouton "Signaler un problème" → redirect `/orders/:id/dispute`

## Panier — State (Signals)

```typescript
export class CartStore {
  items = signal<CartItem[]>(this.loadFromStorage());
  total = computed(() => this.items().reduce((s, i) => s + i.price * i.quantity, 0));

  add(item: CartItem) {
    this.items.update(items => {
      const existing = items.find(i => i.product_id === item.product_id);
      if (existing) return items.map(i => i === existing ? { ...i, quantity: i.quantity + 1 } : i);
      return [...items, item];
    });
    this.saveToStorage();
  }
}
```

## Performance

| Optimisation | Détail |
|---|---|
| Lazy loading routes | Toutes les routes |
| Virtual scroll | Liste produits (CDK) |
| Debounce recherche | 300ms |
| Images WebP | ng-optimized-image |
| Cache SW | Produits vus, accueil |
| Pas de Material | Économise ~200KB |

## PWA

```json
// ngsw-config.json (extrait)
{
  "dataGroups": [
    { "name": "products", "urls": ["/api/v1/products/**"], 
      "cacheConfig": { "strategy": "freshness", "maxAge": "1h" } },
    { "name": "categories", "urls": ["/api/v1/categories"],
      "cacheConfig": { "strategy": "performance", "maxAge": "24h" } }
  ]
}
```

## Capacitor (APK Play Store)

Plugins utilisés :
- `@capacitor/push-notifications` → notifs nouvelles commandes
- `@capacitor/haptics` → feedback boutons
- `@capacitor/browser` → ouvrir CinetPay widget

```bash
npx cap add android
npx cap sync
npx cap run android
```
