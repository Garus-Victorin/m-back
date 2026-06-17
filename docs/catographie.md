## Cartographie technique du backend à construire

Voici une **cartographie claire, réaliste et actionnable** du backend `Marketify/m-back`, basée sur :

- **l’état réel du code**
- **les docs que vous avez dans le projet**
- **la cible métier visible** : admin, seller, shopper/customer, driver, finance, modération

---

# 1. Vision cible

Le backend doit devenir une **API centrale** pour plusieurs clients :

- **Admin app / panel**
- **Seller app**
- **Shopper / customer app**
- **Driver / Go app**

## Rôle du backend
Il devra gérer :

- authentification
- comptes utilisateurs
- boutiques vendeurs
- catalogue produits
- panier et commandes
- paiement
- livraison
- validation KYC
- modération
- finance/reporting
- notifications

---

# 2. Architecture cible recommandée

Vu l’état actuel du projet, je recommande une architecture Laravel simple, propre, sans sur-ingénierie :

```text
m-back/
├─ app/
│  ├─ Http/
│  │  ├─ Controllers/
│  │  │  └─ Api/
│  │  ├─ Middleware/
│  │  ├─ Requests/
│  │  └─ Resources/
│  ├─ Models/
│  ├─ Services/
│  ├─ Actions/
│  ├─ Policies/
│  ├─ Enums/
│  ├─ Notifications/
│  ├─ Jobs/
│  └─ Providers/
├─ database/
│  ├─ migrations/
│  ├─ seeders/
│  └─ factories/
├─ routes/
│  ├─ api.php
│  ├─ web.php
│  └─ console.php
└─ tests/
   ├─ Feature/
   └─ Unit/
```

## Principe
- **Controllers** : reçoivent la requête et renvoient la réponse
- **Requests** : validation
- **Services / Actions** : logique métier
- **Models** : persistance
- **Resources** : format JSON stable
- **Policies** : autorisation
- **Jobs** : tâches async
- **Notifications** : emails/SMS/push éventuels

---

# 3. Modules métier à construire

## 3.1 Auth & Identity
### But
Permettre à chaque type d’utilisateur d’accéder à ses fonctionnalités.

### Entités
- `User`

### Fonctions
- inscription
- connexion
- déconnexion
- récupération du profil
- changement de mot de passe
- vérification email / téléphone
- refresh ou révocation de token

### Endpoints typiques
- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET /api/auth/me`
- `PATCH /api/auth/password`

### À prévoir
- auth API par token
- rôles : `admin`, `moderator`, `finance`, `seller`, `customer`, `driver`

> Recommandation : utiliser **Laravel Sanctum** pour démarrer proprement.

---

## 3.2 Users & Profiles
### But
Gérer les profils des différents acteurs.

### Entités possibles
- `users`
- `user_addresses`
- `user_devices` ou `user_tokens`
- éventuellement `user_documents`

### Fonctions
- profil utilisateur
- mise à jour avatar / téléphone / adresse
- activation / désactivation
- consultation d’un utilisateur par admin

---

## 3.3 KYC & Validation
### But
Valider les vendeurs et éventuellement certains chauffeurs.

### Entités possibles
- `kyc_submissions`
- `kyc_documents`
- ou garder une version simplifiée sur `users` au début

### Fonctions
- upload des pièces
- soumission KYC
- validation / rejet
- historique de décision
- note du modérateur

### Endpoints typiques
- `POST /api/kyc/submit`
- `GET /api/kyc/status`
- `POST /api/admin/kyc/{user}/approve`
- `POST /api/admin/kyc/{user}/reject`

---

## 3.4 Shops / Sellers
### But
Un vendeur doit posséder une boutique et gérer son activité.

### Entités
- `shops`
- `shop_users` si plusieurs managers par boutique
- `shop_settings`

### Fonctions
- création de boutique
- rattachement vendeur → boutique
- configuration boutique
- statut boutique (active, pending, suspended)
- horaires, adresse, branding

### Endpoints typiques
- `POST /api/seller/shops`
- `GET /api/seller/shops/me`
- `PATCH /api/seller/shops/{shop}`
- `GET /api/admin/shops`

---

## 3.5 Catalogue
### But
Exposer les produits disponibles à la vente.

### Entités
- `categories`
- `products`
- `product_images`
- `product_variants`
- `product_stocks`
- éventuellement `brands`

### Fonctions
- CRUD produit
- gestion stock
- publication / dépublication
- prix, promo, variantes
- classement par catégorie

### Endpoints typiques
- `GET /api/products`
- `GET /api/products/{slug}`
- `POST /api/seller/products`
- `PATCH /api/seller/products/{product}`
- `DELETE /api/seller/products/{product}`

### Besoin important
- filtrage
- pagination
- recherche
- tri

---

## 3.6 Cart / Checkout
### But
Permettre au client de préparer sa commande.

### Entités
- `carts`
- `cart_items`

### Fonctions
- ajouter au panier
- retirer / modifier quantité
- recalculer montants
- valider disponibilité stock
- préparer checkout

### Endpoints
- `GET /api/cart`
- `POST /api/cart/items`
- `PATCH /api/cart/items/{item}`
- `DELETE /api/cart/items/{item}`
- `POST /api/checkout`

---

## 3.7 Orders
### But
Cœur transactionnel du système.

### Entités
- `orders`
- `order_items`
- `order_status_histories`
- `order_addresses`

### Statuts recommandés
- `pending`
- `confirmed`
- `paid`
- `preparing`
- `ready_for_pickup`
- `assigned_to_driver`
- `in_delivery`
- `delivered`
- `cancelled`
- `refunded`

### Fonctions
- création commande
- consultation commande
- annulation
- confirmation vendeur
- affectation livraison
- historique d’état

### Endpoints
- `POST /api/orders`
- `GET /api/orders`
- `GET /api/orders/{order}`
- `POST /api/seller/orders/{order}/confirm`
- `POST /api/orders/{order}/cancel`

---

## 3.8 Payments
### But
Encaisser, tracer, réconcilier.

### Entités
- `payments`
- `payment_transactions`
- `refunds`
- `payouts` plus tard

### Intégration
D’après votre doc cible : **CinetPay**

### Fonctions
- initier paiement
- recevoir callback/webhook
- vérifier transaction
- marquer commande payée
- gérer échec paiement
- rembourser si nécessaire

### Endpoints
- `POST /api/payments/initiate`
- `POST /api/payments/webhook/cinetpay`
- `GET /api/payments/{payment}`

### Important
Le webhook doit :
- être sécurisé
- être idempotent
- journaliser les requêtes reçues

---

## 3.9 Delivery / Driver
### But
Gérer la livraison des commandes.

### Entités
- `drivers`
- `deliveries`
- `delivery_assignments`
- `delivery_tracking_events`

### Fonctions
- disponibilité driver
- affectation commande
- prise en charge
- suivi d’état livraison
- confirmation de livraison

### Endpoints
- `GET /api/driver/orders/available`
- `POST /api/driver/deliveries/{delivery}/accept`
- `POST /api/driver/deliveries/{delivery}/picked-up`
- `POST /api/driver/deliveries/{delivery}/delivered`

---

## 3.10 Notifications
### But
Informer les acteurs d’événements importants.

### Types
- commande créée
- commande confirmée
- paiement validé
- livraison assignée
- KYC approuvé/rejeté

### Canaux
- email
- SMS
- push plus tard
- log interne au début

---

## 3.11 Admin / Moderation / Finance
### But
Offrir le pilotage global.

### Sous-domaines
- gestion utilisateurs
- validation KYC
- modération boutiques/produits
- vue globale commandes
- vue paiements
- exports/statistiques

### Endpoints possibles
- `GET /api/admin/users`
- `PATCH /api/admin/users/{user}/status`
- `GET /api/admin/orders`
- `GET /api/admin/payments`
- `GET /api/admin/shops`
- `POST /api/admin/products/{product}/suspend`

---

# 4. Modèle de données cible minimal

Voici le **socle minimal** à prévoir.

## Déjà existant
- `users`

## À ajouter rapidement
- `shops`
- `categories`
- `products`
- `product_images`
- `carts`
- `cart_items`
- `orders`
- `order_items`
- `payments`
- `deliveries`

## À ajouter ensuite
- `kyc_submissions`
- `order_status_histories`
- `delivery_tracking_events`
- `refunds`
- `notifications`
- `payouts`

---

# 5. Relations Eloquent à prévoir

## `User`
- hasOne `Shop` ou hasMany selon votre modèle
- hasMany `Order`
- hasMany `Payment`
- hasMany `Delivery` si driver
- peut avoir un rôle métier

## `Shop`
- belongsTo `User` owner
- hasMany `Product`
- hasMany `Order`

## `Product`
- belongsTo `Shop`
- belongsTo `Category`
- hasMany `ProductImage`

## `Order`
- belongsTo `User`
- belongsTo `Shop`
- hasMany `OrderItem`
- hasOne `Payment`
- hasOne `Delivery`

## `Delivery`
- belongsTo `Order`
- belongsTo `User` driver

---

# 6. Architecture API recommandée

## Versionnement
Prévoir dès le début :

- `/api/v1/...`

Même si au départ vous gardez `/api/...`, le versionnement évite des migrations douloureuses plus tard.

## Format de réponse
Je recommande une réponse JSON homogène :

### Succès
```json
{
  "success": true,
  "message": "Order created successfully",
  "data": {}
}
```

### Erreur
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

## Pagination
Toujours standardiser :
- `data`
- `links`
- `meta`

Laravel `ResourceCollection` convient très bien.

---

# 7. Sécurité à mettre en place

## Auth
- Sanctum
- routes protégées par middleware auth

## Autorisation
- `Policies`
- `Gate` pour actions admin globales

## CORS
Créer `config/cors.php` et l’ajuster pour :
- admin frontend
- seller app
- shopper app
- driver app

## Rate limiting
Au minimum :
- auth endpoints
- webhooks
- endpoints publics de catalogue

## Validation
Toute entrée doit passer par des `FormRequest`.

## Webhooks paiement
- signature ou validation de source
- logs
- idempotence

---

# 8. Organisation du code recommandée

## Controllers
Exemples :

- `App\Http\Controllers\Api\AuthController`
- `App\Http\Controllers\Api\ProductController`
- `App\Http\Controllers\Api\OrderController`
- `App\Http\Controllers\Api\PaymentWebhookController`
- `App\Http\Controllers\Api\Admin\UserController`
- `App\Http\Controllers\Api\Seller\ProductController`
- `App\Http\Controllers\Api\Driver\DeliveryController`

## Requests
Exemples :

- `LoginRequest`
- `RegisterRequest`
- `StoreProductRequest`
- `UpdateProductRequest`
- `StoreOrderRequest`

## Resources
Exemples :

- `UserResource`
- `ShopResource`
- `ProductResource`
- `OrderResource`
- `PaymentResource`

## Services / Actions
Exemples :

- `AuthService`
- `CreateOrderService`
- `PaymentService`
- `CinetPayService`
- `AssignDriverService`

---

# 9. Phasage recommandé

## Phase 0 — Stabilisation du socle
Objectif : rendre `m-back` proprement exécutable comme API.

À faire :
- créer `routes/api.php`
- brancher `api.php` dans `bootstrap/app.php`
- installer auth API
- configurer CORS
- configurer `.env`
- choisir la DB réelle : MySQL si c’est votre cible
- garder SQLite seulement pour les tests

## Phase 1 — Auth + Users + Shops
À faire :
- login/register/logout/me
- rôles
- shop seller
- profil user
- base KYC

## Phase 2 — Catalogue
À faire :
- catégories
- produits
- images
- stock
- listing public

## Phase 3 — Commande
À faire :
- panier
- checkout
- orders
- historique commandes

## Phase 4 — Paiement
À faire :
- init paiement
- webhook
- synchronisation état paiement/commande

## Phase 5 — Livraison
À faire :
- drivers
- affectation
- suivi

## Phase 6 — Admin & reporting
À faire :
- KYC admin
- modération
- supervision paiements/commandes

---

# 10. Priorités immédiates

Vu le code actuel, les **prochaines vraies priorités** sont :

## Priorité A
- `routes/api.php`
- `AuthController`
- auth token
- `GET /api/me`

## Priorité B
- migrations métier principales
- `Shop`
- `Category`
- `Product`
- `Order`
- `Payment`

## Priorité C
- JSON resources
- requests de validation
- tests feature réels

---

# 11. Ce qu’il ne faut pas faire tout de suite

Pour éviter de ralentir le projet :

- ne pas surcomplexifier avec microservices
- ne pas créer 50 dossiers domain-driven trop tôt
- ne pas intégrer trop de packages tant que le flux principal n’est pas stable
- ne pas développer d’admin panel complet avant d’avoir les APIs cœur

---

# 12. Ma recommandation technique finale

Pour ce projet, la meilleure trajectoire est :

1. **faire un backend monolithique Laravel propre**
2. **ajouter Sanctum**
3. **construire les modules métier par couches simples**
4. **stabiliser commandes + paiements**
5. **ajouter livraison et administration ensuite**

C’est la voie la plus rapide, robuste et maintenable vu votre base actuelle.

---

# 13. Résumé ultra-pratique

## Aujourd’hui
`m-back` = squelette Laravel + `User`

## Demain
`m-back` doit devenir :

- une **API REST versionnée**
- sécurisée par token
- organisée par modules :
  - auth
  - users
  - shops
  - catalogue
  - cart
  - orders
  - payments
  - delivery
  - admin

## Premier livrable intelligent
Le **MVP backend** devrait être :

- auth
- profil user
- boutique seller
- catalogue produit
- création commande
- initiation paiement
- webhook paiement
