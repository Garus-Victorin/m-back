# API — Routes & Contrats

Base URL : `https://api.marketify.bj/api/v1`

Auth : Bearer token via Laravel Sanctum (`Authorization: Bearer {token}`)

---

## Auth

```
POST   /auth/register          # Inscription client
POST   /auth/login             # Connexion tous rôles
POST   /auth/logout            # Invalider token
POST   /auth/refresh           # Refresh token
GET    /auth/me                # Utilisateur connecté
```

---

## Catalogue (public)

```
GET    /products               # Liste produits (filtrés, paginés)
GET    /products/{id}          # Détail produit
GET    /products/search        # Recherche full-text (?q=chaussure&category=2)
GET    /categories             # Arbre catégories
GET    /shops/{id}             # Vitrine vendeur + ses produits
```

### Paramètres GET /products

| Param | Type | Exemple |
|---|---|---|
| `q` | string | `?q=chaussure` |
| `category_id` | int | `?category_id=3` |
| `min_price` | int | `?min_price=1000` |
| `max_price` | int | `?max_price=50000` |
| `city` | string | `?city=Cotonou` |
| `sort` | string | `?sort=price_asc` |
| `page` | int | `?page=2` |

---

## Vendeur (rôle: seller)

```
POST   /seller/shop            # Créer boutique + upload KYC
PUT    /seller/shop            # Modifier boutique
GET    /seller/dashboard       # Stats CA, commandes, ruptures

GET    /seller/products        # Ses produits
POST   /seller/products        # Créer produit
PUT    /seller/products/{id}   # Modifier
DELETE /seller/products/{id}   # Supprimer

GET    /seller/orders          # Ses commandes reçues
PUT    /seller/orders/{id}/ready  # Marquer prêt pour enlèvement

GET    /seller/finance         # Solde, historique virements
POST   /seller/finance/withdraw   # Demander retrait
```

---

## Client (rôle: customer)

```
GET    /cart                   # Récupérer panier (depuis API, synchro)
POST   /cart/items             # Ajouter item
PUT    /cart/items/{id}        # Modifier quantité
DELETE /cart/items/{id}        # Supprimer item

POST   /orders                 # Créer commande
GET    /orders                 # Historique commandes
GET    /orders/{id}            # Détail commande + statut
POST   /orders/{id}/confirm    # Confirmer réception (OTP)
POST   /orders/{id}/dispute    # Ouvrir litige

GET    /addresses              # Adresses sauvegardées
POST   /addresses              # Ajouter adresse
```

---

## Paiement

```
POST   /payments/initiate      # Initier paiement → retourne URL CinetPay
POST   /webhooks/cinetpay      # Webhook CinetPay (public, signé HMAC)
GET    /payments/{id}/status   # Statut paiement
```

---

## Livreur (rôle: driver)

```
PUT    /driver/availability    # Toggle en ligne/hors ligne
PUT    /driver/location        # Update position GPS
GET    /driver/deliveries      # Courses disponibles autour
POST   /driver/deliveries/{id}/accept   # Accepter course
PUT    /driver/deliveries/{id}/pickup   # Confirmer pickup (scan QR)
PUT    /driver/deliveries/{id}/delivered  # Confirmer livraison (OTP)
GET    /driver/earnings        # Gains et historique
```

---

## Chat

```
GET    /conversations          # Liste conversations de l'utilisateur
GET    /conversations/{id}/messages  # Messages d'une conversation
POST   /conversations/{id}/messages  # Envoyer message
```

---

## Admin (rôle: admin | moderator | finance)

Géré via Filament. Routes API admin uniquement pour actions programmatiques :

```
POST   /admin/orders/{id}/refund      # Rembourser commande
POST   /admin/payouts/batch           # Lancer virements batch
GET    /admin/analytics/summary       # Stats plateforme
```

---

## Format de réponse standard

```json
{
  "success": true,
  "data": { ... },
  "meta": { "page": 1, "total": 120 }
}
```

## Format d'erreur

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "price": ["Le prix est requis"]
  }
}
```

## Codes HTTP utilisés

| Code | Usage |
|---|---|
| 200 | Succès |
| 201 | Créé |
| 400 | Erreur de validation |
| 401 | Non authentifié |
| 403 | Non autorisé (rôle insuffisant) |
| 404 | Ressource introuvable |
| 409 | Conflit (ex: stock insuffisant) |
| 422 | Données invalides |
| 500 | Erreur serveur |
