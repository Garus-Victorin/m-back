# Roadmap Backend Seller Pro — Marketify

> **Projet :** `Marketify/m-back`
> **Portée :** backend complet de la partie `Sell`
> **Date de référence :** 2026-06-17
> **Objectif :** fournir un plan d’implémentation backend seller réellement exploitable en production, solide, sécurisé, auditable et maintenable.

---

## 1. Objet du document

Ce document définit **la roadmap backend complète de la partie vendeur** de Marketify.

Il ne s’agit pas d’une simple liste d’idées produit. C’est un **plan d’ingénierie backend** qui doit servir de référence pour construire un module seller :

- cohérent avec l’application `marketify-sell`
- robuste côté API
- sûr côté sécurité
- propre côté architecture
- traçable côté exploitation
- testable et maintenable dans le temps

---

## 2. Décisions structurantes à retenir

## 2.1 On suit le backend réel, pas la doc théorique

La documentation du projet contient des écarts entre la cible imaginée et l’état réel observé.

### Réalité observée
- framework réel : **Laravel 13**
- base réelle : **MySQL**
- auth réelle : **Sanctum**
- tests réels : **Pest / PHPUnit**

### Décision retenue
Le roadmap seller doit être construit sur :

- **Laravel 13**
- **MySQL + InnoDB**
- **Redis** pour cache / queue dès que les jobs seller deviennent sérieux
- **Sanctum** pour l’auth API

### Conséquence
Les docs qui parlent encore de **Laravel 11** ou **PostgreSQL / jsonb** ne doivent **pas piloter l’implémentation réelle**.

On garde la vision métier, mais on corrige les hypothèses techniques pour coller au projet réellement présent.

---

## 2.2 Niveau d’exigence

La partie seller touche à des domaines sensibles :

- identité vendeur
- catalogue publié au public
- commandes reçues
- argent gagné
- demandes de retrait
- justificatifs KYC

Donc le backend seller doit être conçu comme un **sous-système critique**.

### Règles non négociables
1. aucun endpoint seller sensible sans authentification
2. aucune mutation seller sans autorisation explicite
3. aucune donnée financière calculée par confiance frontend
4. aucune transition de statut produit / shop / order hors règles métier
5. aucun fichier KYC stocké en public
6. aucune action admin/seller sensible sans audit trail
7. aucun endpoint important sans validation stricte et tests
8. aucune décision métier critique laissée au frontend

---

## 3. Ce que la partie Sell implique vraiment côté backend

D’après `docs/docs/app-sell.md`, l’application vendeur couvre les écrans suivants :

- `/login`
- `/onboarding`
- `/dashboard`
- `/products`
- `/products/new`
- `/products/:id/edit`
- `/orders`
- `/orders/:id`
- `/chat`
- `/finance`
- `/settings`

Cela signifie que le backend seller complet ne se limite pas à `CRUD product + dashboard`.

Le backend seller complet doit couvrir au minimum :

1. **auth seller**
2. **onboarding boutique**
3. **KYC seller**
4. **gestion boutique**
5. **gestion catalogue vendeur**
6. **gestion médias produit**
7. **gestion variantes / stock**
8. **modération et publication**
9. **consultation et traitement des commandes vendeur**
10. **timeline commande seller**
11. **finance seller**
12. **demandes de retrait**
13. **paramètres boutique**
14. **chat seller**
15. **notifications seller**
16. **audit, sécurité, permissions et observabilité**

---

## 4. Problèmes et lacunes à corriger explicitement

Voici les insuffisances ou angles morts à combler pour avoir une partie seller de niveau professionnel.

## 4.1 Lacunes documentaires

### Incohérences de stack
- doc backend mentionne Laravel 11
- doc architecture mentionne PostgreSQL
- doc database annonce MySQL mais inclut encore des types `jsonb`

### Décision
- standardiser la doc seller backend sur **Laravel 13 + MySQL**
- remplacer les notions `jsonb` par `json` dans la conception MySQL
- ne jamais documenter une stack non alignée avec le repo réel

---

## 4.2 Lacunes métiers seller

Il manque ou reste sous-décrit :

- vrai onboarding seller piloté par statuts
- cycle de vie KYC complet
- publication produit conditionnée par validation boutique/KYC
- workflow produit `draft -> pending_review -> active -> rejected -> archived`
- gestion d’images produit sécurisée
- gestion variantes / stock réservés / rupture
- lecture détaillée des commandes seller
- actions seller sur commandes bornées par statut
- vraie vue finance seller
- retrait vendeur sécurisé et auditable
- paramètres Mobile Money et payout
- règles de suspension seller / boutique
- anti-fraude seller minimale
- journal d’activité seller

---

## 4.3 Lacunes techniques seller

- pas de Policies systématiques
- checks de rôle potentiellement dispersés dans les contrôleurs
- pas de stratégie claire d’idempotence pour les retraits
- pas de rate limiting dédié seller
- pas de convention d’erreurs métier seller
- pas de request correlation id
- pas de garde-fous sur ownership des ressources
- pas de vision claire des jobs async seller
- pas de contrat strict pour uploads
- pas de runbook d’incident seller

---

## 5. Vision cible du backend Seller

Le backend seller doit permettre le scénario suivant, sans zone grise :

1. un utilisateur obtient le rôle `seller`
2. il soumet son onboarding boutique
3. il transmet ses pièces KYC
4. le système place la boutique en état `pending_review`
5. l’admin valide ou rejette la boutique / KYC
6. le vendeur configure sa boutique et ses moyens de payout
7. il crée des produits en brouillon
8. il soumet les produits à validation si nécessaire
9. les produits actifs deviennent visibles côté shop
10. il reçoit des commandes payées
11. il traite les commandes selon des transitions encadrées
12. il suit son chiffre d’affaires et ses soldes
13. il demande un retrait selon des règles d’éligibilité
14. toute action sensible reste journalisée et administrable

---

## 6. Architecture cible recommandée pour la partie Seller

## 6.1 Style d’architecture

Le projet reste un **modular monolith** Laravel.

### Principe
On évite le microservice prématuré. En revanche, on **découpe clairement le domaine seller**.

## 6.2 Découpage logique recommandé

```text
app/
├── Domain/
│   ├── Seller/
│   │   ├── Shop/
│   │   ├── Kyc/
│   │   ├── Product/
│   │   ├── Order/
│   │   ├── Finance/
│   │   └── Settings/
│   ├── Shared/
│   └── Support/
├── Actions/
│   └── Seller/
├── Http/
│   ├── Controllers/Api/V1/Seller/
│   ├── Requests/Seller/
│   └── Resources/Seller/
├── Policies/
├── Jobs/
├── Events/
├── Listeners/
└── Services/
```

### Règle stricte
Les contrôleurs seller doivent rester minces.

Ils ne doivent pas contenir la vraie logique métier critique.

La logique critique doit vivre dans :

- `Actions`
- `Services`
- `Policies`
- éventuellement `State/Transition services` pour les statuts complexes

---

## 6.3 Modèle de domaine seller

Le domaine seller doit au minimum couvrir les agrégats suivants :

### Shop
- identité boutique
- statut boutique
- activation / suspension
- branding
- contact
- payout settings
- paramètres d’exploitation

### SellerKyc
- type de document
- statut de vérification
- pièces soumises
- historique de décision
- motif de rejet

### SellerProduct
- brouillon / publication / rejet
- images
- variantes
- stock
- conformité catégorie
- visibilité

### SellerOrderView
- commandes reçues par boutique
- timeline statut
- adresse et informations utiles
- ligne de commande seller-centric

### SellerFinance
- solde en attente
- solde disponible
- total gagné
- commissions
- retraits demandés
- retraits payés / échoués

### SellerSettings
- numéro Mobile Money
- nom bénéficiaire
- préférences notifications
- horaires si la boutique les expose
- zones livrables si la logique future le demande

---

## 7. Modèle de données seller à viser

## 7.1 Tables seller minimales

En complément de l’existant, la partie seller professionnelle doit couvrir au moins :

### `shops`
Champs attendus ou à confirmer :
- `id`
- `user_id`
- `name`
- `slug`
- `description`
- `logo_path`
- `banner_path`
- `phone`
- `email`
- `city`
- `address_line`
- `status`
- `is_public`
- `activated_at`
- `suspended_at`
- `suspension_reason`
- `commission_rate`
- `created_at`
- `updated_at`

### `seller_kyc_submissions`
- `id`
- `user_id`
- `shop_id`
- `document_type`
- `document_front_path`
- `document_back_path`
- `selfie_path` si requis
- `mobile_money_number`
- `mobile_money_provider`
- `status`
- `reviewed_by`
- `reviewed_at`
- `rejection_reason`
- `created_at`
- `updated_at`

### `products`
Champs seller importants :
- `shop_id`
- `category_id`
- `title`
- `slug`
- `short_description`
- `description`
- `sku`
- `price_cents`
- `compare_price_cents`
- `stock`
- `reserved_stock`
- `status`
- `published_at`
- `rejection_reason`
- `is_featured` si admin seulement
- `created_at`
- `updated_at`

### `product_images`
- `product_id`
- `path`
- `position`
- `alt_text` optionnel
- `created_at`

### `product_variants`
- `product_id`
- `sku`
- `attribute_name`
- `attribute_value`
- `extra_price_cents`
- `stock`
- `is_active`

### `seller_withdrawal_requests`
- `id`
- `shop_id`
- `user_id`
- `amount_cents`
- `currency`
- `mobile_money_provider`
- `mobile_money_number`
- `status`
- `processed_by`
- `processed_at`
- `failure_reason`
- `provider_reference`
- `idempotency_key`
- `created_at`
- `updated_at`

### `audit_logs` ou équivalent
- `actor_id`
- `action`
- `target_type`
- `target_id`
- `before`
- `after`
- `ip_address`
- `request_id`
- `created_at`

---

## 7.2 Contraintes indispensables

### Base
- toutes les tables en **InnoDB**
- toutes les FK réellement créées
- index sur toutes les FK
- unicité sur `shops.slug`, `products.slug`
- unicité sur `products.sku` si SKU global, ou composite selon règle retenue

### Index recommandés seller
- `shops(user_id)`
- `shops(status)`
- `products(shop_id, status)`
- `products(category_id, status)`
- `products(shop_id, updated_at)`
- `seller_kyc_submissions(user_id, status)`
- `seller_withdrawal_requests(shop_id, status)`
- `seller_withdrawal_requests(idempotency_key)`

### Règles d’intégrité
- un seller ne possède qu’une boutique active à la fois si c’est la règle produit retenue
- un produit appartient obligatoirement à une boutique
- une demande de retrait doit pointer vers un shop et un user cohérents
- les montants doivent être stockés en entiers (`*_cents`)
- aucun `float` pour le financier

---

## 8. Machine à états à formaliser

## 8.1 Statut boutique

```text
draft -> pending_review -> active -> suspended
     -> rejected
```

### Règles
- `draft` : onboarding commencé mais incomplet
- `pending_review` : soumis, attente admin
- `active` : boutique exploitable
- `rejected` : rejet avec motif explicite
- `suspended` : suspension admin, accès seller limité selon politique

## 8.2 Statut KYC seller

```text
not_submitted -> pending -> verified
                         -> rejected
                         -> expired
```

### Règles
- pas de publication produit si KYC requis mais absent ou rejeté
- pas de retrait possible sans KYC `verified`
- changement de statut KYC = audit obligatoire

## 8.3 Statut produit seller

```text
draft -> pending_review -> active -> archived
      -> rejected
      -> suspended
```

### Règles
- `draft` : non visible publiquement
- `pending_review` : soumis à modération
- `active` : visible côté client
- `rejected` : rejet admin avec motif
- `archived` : caché par seller
- `suspended` : blocage admin

### Décision importante
Le seller ne doit **jamais** pouvoir s’auto-publier en production si la marketplace exige modération.

Si une publication automatique est finalement retenue pour certaines catégories, cela doit être porté par une règle métier explicite, pas par un oubli technique.

---

## 9. API Seller cible

Toutes les routes seller doivent être sous :

```text
/api/v1/seller/*
```

avec :
- auth Sanctum
- middleware seller role / abilities
- ownership checks
- rate limiting dédié
- réponses JSON standardisées

---

## 9.1 Auth et contexte seller

### `GET /seller/me`
Retourne :
- user courant
- rôle
- état boutique
- état KYC
- permissions seller utiles au frontend

### `GET /seller/bootstrap`
Endpoint de bootstrap de l’app seller.

Retourne dans une seule réponse :
- user
- shop
- kyc status
- unread counts
- finance summary léger
- capacités UI (`can_publish`, `can_withdraw`, etc.)

### Pourquoi cet endpoint est important
Il évite 5 ou 6 appels au chargement de `marketify-sell`, simplifie l’app frontend et centralise les règles de capacité.

---

## 9.2 Onboarding et shop

### `POST /seller/onboarding`
Crée ou complète l’onboarding seller.

Doit permettre :
- nom boutique
- description
- contact
- ville / adresse
- logo / bannière si déjà prêts

### `GET /seller/shop`
Retourne la boutique courante du seller.

### `PATCH /seller/shop`
Met à jour la boutique.

### `POST /seller/shop/submit-review`
Soumet la boutique à validation.

### `POST /seller/shop/logo`
Upload logo boutique.

### `POST /seller/shop/banner`
Upload bannière boutique.

### `GET /seller/settings`
Retourne les paramètres seller.

### `PATCH /seller/settings`
Met à jour :
- numéro Mobile Money
- provider Mobile Money
- préférences notifications
- éventuellement horaires / options d’exploitation

---

## 9.3 KYC seller

### `GET /seller/kyc`
Retourne la soumission KYC courante et son statut.

### `POST /seller/kyc`
Soumet ou resoumet un dossier KYC.

### `POST /seller/kyc/document-front`
Upload document recto.

### `POST /seller/kyc/document-back`
Upload document verso.

### `POST /seller/kyc/selfie`
Upload selfie de vérification si retenu.

### Règles API KYC
- stockage privé
- antivirus / validation MIME réelle si possible
- taille maximale stricte
- types de fichiers autorisés limités
- suppression de tout accès public direct
- URLs signées temporaires uniquement pour lecture autorisée

---

## 9.4 Dashboard seller

### `GET /seller/dashboard`
Retourne un résumé seller calculé côté serveur.

Contenu minimal :
- ventes du jour
- commandes en attente de préparation
- commandes prêtes
- produits en rupture
- évolution 7 jours
- derniers événements seller

### Décision
Le dashboard doit reposer sur des agrégations back fiables.

Le frontend ne doit jamais recalculer de pseudo-métriques critiques depuis des listes incomplètes.

---

## 9.5 Produits seller

### `GET /seller/products`
Liste paginée des produits du seller.

Filtres whitelistés :
- `status`
- `category_id`
- `q`
- `stock_state`
- `created_from`
- `created_to`

Tri whitelisté :
- `created_at_desc`
- `created_at_asc`
- `updated_at_desc`
- `price_asc`
- `price_desc`
- `stock_asc`
- `stock_desc`

### `POST /seller/products`
Crée un produit seller.

### `GET /seller/products/{product}`
Retourne le détail produit si ownership validée.

### `PATCH /seller/products/{product}`
Met à jour le produit.

### `DELETE /seller/products/{product}`
Suppression logique recommandée.

### `POST /seller/products/{product}/submit-review`
Soumet un produit à modération.

### `POST /seller/products/{product}/archive`
Archive un produit.

### `POST /seller/products/{product}/restore`
Restaure un produit archivé si autorisé.

### `PATCH /seller/products/{product}/stock`
Met à jour uniquement le stock.

### `POST /seller/products/{product}/images`
Ajout d’image(s).

### `DELETE /seller/products/{product}/images/{image}`
Suppression image.

### `PATCH /seller/products/{product}/images/reorder`
Réordonnancement.

### `PUT /seller/products/{product}/variants`
Remplace la liste de variantes de manière contrôlée.

---

## 9.6 Commandes seller

### `GET /seller/orders`
Liste paginée des commandes de la boutique.

Filtres whitelistés :
- `status`
- `date_from`
- `date_to`
- `q`
- `payment_status`

### `GET /seller/orders/{order}`
Détail seller de la commande.

Doit inclure :
- lignes de commande
- client visible selon politique privacy
- adresse utile à la livraison
- timeline statut
- paiement synthétique
- events importants

### `POST /seller/orders/{order}/mark-ready`
Action seller pour marquer une commande prête.

### `POST /seller/orders/{order}/cancel-request`
Permet au seller de demander une annulation si cas prévu.

### `GET /seller/orders/{order}/packing-slip`
Retourne le bon de préparation / impression si prévu.

### Règles critiques
- le seller ne voit que les commandes de sa boutique
- le seller ne peut pas modifier librement le statut
- seules certaines transitions sont autorisées
- toute transition seller doit être journalisée

---

## 9.7 Finance seller

### `GET /seller/finance/summary`
Retourne :
- solde disponible
- solde en attente
- total gagné
- total retiré
- total commissions
- retraits en cours

### `GET /seller/finance/transactions`
Historique seller orienté lecture.

### `GET /seller/finance/withdrawals`
Historique des demandes de retrait.

### `POST /seller/finance/withdrawals`
Crée une demande de retrait.

### Règles incontournables pour `withdrawals`
- KYC vérifié obligatoire
- shop active obligatoire
- montant minimum configurable
- montant <= solde disponible
- numéro payout valide
- idempotence obligatoire
- création auditée
- traitement back-office traçable

---

## 9.8 Chat seller

### `GET /seller/conversations`
Liste les conversations du seller.

### `GET /seller/conversations/{conversation}/messages`
Messages de la conversation.

### `POST /seller/conversations/{conversation}/messages`
Envoi d’un message.

### Garde-fous
- le seller ne doit accéder qu’aux conversations où il est participant
- pièces jointes limitées et sécurisées
- logs anti-abus si besoin
- messages liés à une commande à privilégier plutôt qu’un chat totalement libre

---

## 10. Construction des APIs : exigences de qualité

## 10.1 Contrat de réponse

### Succès
```json
{
  "success": true,
  "message": null,
  "data": {},
  "meta": {},
  "links": {}
}
```

### Erreur
```json
{
  "success": false,
  "message": "Validation failed",
  "code": "VALIDATION_ERROR",
  "errors": {
    "name": ["Le nom est requis"]
  },
  "meta": {
    "request_id": "req_..."
  }
}
```

### Règles
- `code` métier stable
- `request_id` présent au moins sur les erreurs
- pas d’exception brute Laravel exposée en production
- pagination homogène partout

---

## 10.2 Codes HTTP seller

- `200` : lecture / update simple
- `201` : création
- `204` : suppression logique sans payload si retenu
- `401` : non authentifié
- `403` : interdit
- `404` : ressource absente ou non accessible
- `409` : conflit métier (`INVALID_STATUS_TRANSITION`, `INSUFFICIENT_AVAILABLE_BALANCE`, etc.)
- `422` : validation
- `429` : rate limit

---

## 10.3 Validation stricte

Chaque endpoint seller doit avoir un `FormRequest` dédié si l’entrée n’est pas triviale.

À valider explicitement :
- ownership indirecte
- enum values contrôlées
- bornes de prix
- bornes de stock
- nombre max d’images
- type MIME réel
- longueur des textes
- format téléphone Mobile Money
- cohérence variante / stock
- interdiction de champs mass assignables dangereux

---

## 11. Sécurité seller obligatoire

## 11.1 AuthN / AuthZ

### Authentification
- Sanctum conservé
- tokens révoqués si compte suspendu
- seller inactif ou suspendu = accès limité ou bloqué selon endpoint

### Autorisation
Mettre en place des `Policies` au minimum pour :
- `ShopPolicy`
- `ProductPolicy`
- `OrderPolicy`
- `ConversationPolicy`
- `WithdrawalRequestPolicy`

### Interdiction absolue
Ne jamais se contenter d’un simple filtre `where('user_id', auth()->id())` dans tous les contrôleurs sans policy centralisée.

---

## 11.2 Rate limiting seller

Politique recommandée :
- `/seller/bootstrap` : `30/min/user`
- `/seller/products*` lecture : `60/min/user`
- `/seller/products*` mutation : `30/min/user`
- `/seller/orders*` : `60/min/user`
- `/seller/finance/withdrawals` : `5/min/user`
- uploads : seuil plus bas + taille contrôlée
- chat message : `20/min/user`

---

## 11.3 Uploads et médias

### Exigences
- stockage privé pour KYC
- stockage organisé pour médias seller
- nommage non prédictible
- validation MIME réelle
- limite taille stricte
- resize / optimisation image côté backend ou pipeline dédié
- refus des exécutables déguisés
- suppression ou versioning des fichiers orphelins

### Décision
Les URLs de fichiers KYC ne doivent jamais être stockées comme URLs publiques finales.

On stocke des **paths internes**, et on sert via URL signée si nécessaire.

---

## 11.4 Audit seller

Actions à auditer :
- création / mise à jour shop
- soumission KYC
- validation / rejet KYC
- création / modification produit
- soumission produit en revue
- activation / suspension produit
- retrait demandé
- retrait traité
- changement de payout settings
- transition seller order (`mark-ready`, etc.)

---

## 12. Logique métier seller à centraliser

## 12.1 Actions backend recommandées

Créer des actions ou services explicites :

- `CreateSellerOnboardingAction`
- `SubmitSellerShopForReviewAction`
- `SubmitSellerKycAction`
- `UpdateSellerPayoutSettingsAction`
- `CreateSellerProductAction`
- `UpdateSellerProductAction`
- `SubmitSellerProductForReviewAction`
- `ArchiveSellerProductAction`
- `UpdateSellerStockAction`
- `GetSellerDashboardAction`
- `MarkSellerOrderReadyAction`
- `CreateWithdrawalRequestAction`

### Pourquoi
Cela évite :
- contrôleurs massifs
- duplication métier
- règles cachées
- transitions de statut incohérentes

---

## 12.2 Règles métier importantes à codifier

### Publication produit
Un produit ne peut pas être publié si :
- la boutique n’est pas `active`
- le KYC seller n’est pas `verified` si requis
- le produit n’a pas de catégorie valide
- le produit n’a pas de prix valide
- le produit n’a pas d’image minimale si la marketplace l’exige

### Retrait vendeur
Un retrait ne peut pas être demandé si :
- la boutique n’est pas active
- le KYC n’est pas validé
- le solde disponible est insuffisant
- le montant est sous le minimum
- un retrait identique est rejoué avec la même clé

### Commande seller
Le seller ne peut marquer `ready` que si :
- la commande appartient à sa boutique
- le paiement est confirmé
- la commande est dans un statut compatible
- aucun blocage litige / annulation n’est ouvert

---

## 13. Jobs, événements et asynchrone seller

La partie seller ne doit pas tout faire en synchrone.

## 13.1 Jobs recommandés
- optimisation d’images produit
- scan ou contrôle des uploads
- recalcul agrégats dashboard
- notification seller
- export CSV seller si ajouté plus tard
- synchronisation payout status

## 13.2 Événements utiles
- `SellerShopSubmitted`
- `SellerKycSubmitted`
- `SellerKycVerified`
- `SellerProductSubmitted`
- `SellerProductApproved`
- `SellerProductRejected`
- `SellerOrderReady`
- `SellerWithdrawalRequested`
- `SellerWithdrawalProcessed`

### Règle
Les événements ne remplacent pas la logique transactionnelle critique.

Ils doivent être utilisés pour les effets secondaires, pas pour introduire de l’ambiguïté dans les opérations principales.

---

## 14. Roadmap d’implémentation par phases

# Phase 0 — Assainissement préalable

## Objectif
Corriger les fondations avant d’empiler de nouvelles fonctionnalités seller.

## À faire
- [ ] aligner la doc seller sur Laravel 13 + MySQL
- [ ] vérifier les tables en InnoDB
- [ ] corriger les types incompatibles MySQL (`jsonb` -> `json` dans la conception)
- [ ] vérifier FK et index seller critiques
- [ ] lister les endpoints seller déjà implémentés et leurs écarts avec la cible
- [ ] normaliser les conventions de réponse API

## Critère de sortie
- [ ] le socle documentaire et technique ne se contredit plus

---

# Phase 1 — Hardening du socle seller

## Objectif
Sécuriser la base seller avant d’enrichir la feature set.

## À faire
- [ ] introduire les Policies seller
- [ ] centraliser les checks d’autorisation
- [ ] ajouter rate limits seller
- [ ] ajouter request id / correlation id
- [ ] introduire audit log sur actions seller sensibles
- [ ] durcir la gestion des erreurs métier

## Critère de sortie
- [ ] les endpoints seller existants sont sécurisés et audités

---

# Phase 2 — Onboarding, boutique et KYC complets

## Objectif
Faire de l’entrée seller un vrai workflow exploitable.

## À faire
- [ ] créer le workflow onboarding seller
- [ ] supporter shop draft / submit / review / active / rejected / suspended
- [ ] créer le module KYC seller complet
- [ ] stockage privé des documents KYC
- [ ] rejet motivé et historique de review
- [ ] compléter les settings payout seller

## Critère de sortie
- [ ] un vendeur peut être onboardé, vérifié, activé et administré proprement

---

# Phase 3 — Produits seller niveau production

## Objectif
Passer du CRUD simple à une vraie gestion catalogue seller.

## À faire
- [ ] produit draft / review / active / rejected / archived
- [ ] images produit sécurisées
- [ ] variantes produit propres
- [ ] SKU robustes
- [ ] mise à jour stock dédiée
- [ ] filtres / tri / pagination propres
- [ ] soumission à modération
- [ ] archivage plutôt que suppression destructive

## Critère de sortie
- [ ] le seller peut gérer un catalogue sérieux sans casser la cohérence métier

---

# Phase 4 — Dashboard seller utile et fiable

## Objectif
Donner au vendeur une vision fiable de son activité.

## À faire
- [ ] endpoint bootstrap seller
- [ ] endpoint dashboard seller
- [ ] agrégats 7 jours / 30 jours
- [ ] commandes récentes
- [ ] alertes rupture
- [ ] indicateurs calculés côté serveur

## Critère de sortie
- [ ] l’application seller charge vite et affiche des métriques fiables

---

# Phase 5 — Commandes seller complètes

## Objectif
Permettre au seller de traiter correctement les commandes reçues.

## À faire
- [ ] listing seller orders paginé
- [ ] détail commande seller complet
- [ ] timeline statut
- [ ] action `mark-ready`
- [ ] politique de lecture privacy claire
- [ ] impression bon si retenue
- [ ] garde-fous sur transitions

## Critère de sortie
- [ ] le seller sait consulter et traiter ses commandes sans pouvoir sortir du workflow métier

---

# Phase 6 — Finance seller et retraits

## Objectif
Professionnaliser la partie argent côté seller.

## À faire
- [ ] finance summary
- [ ] historique transactions seller
- [ ] historique retraits
- [ ] création retrait seller
- [ ] idempotence sur retrait
- [ ] règles d’éligibilité strictes
- [ ] audit financier seller
- [ ] préparation du traitement admin / payout provider

## Critère de sortie
- [ ] le seller voit des soldes explicables et peut demander un retrait de manière sûre

---

# Phase 7 — Chat et notifications seller

## Objectif
Compléter l’usage quotidien de l’app seller.

## À faire
- [ ] conversations seller
- [ ] messages seller
- [ ] permissions conversation strictes
- [ ] notifications seller sur événements clés
- [ ] gestion des pièces jointes si conservées

## Critère de sortie
- [ ] le seller reçoit et consulte les échanges utiles dans un cadre sécurisé

---

# Phase 8 — Qualité, tests et exploitation

## Objectif
Rendre le module seller réellement prêt pour un projet pro.

## À faire
- [ ] tests feature sur tous les endpoints seller majeurs
- [ ] tests policies seller
- [ ] tests upload KYC / médias
- [ ] tests transitions produit / shop / order
- [ ] tests finance seller et retraits
- [ ] tests de non-régression d’ownership
- [ ] documentation API seller finale
- [ ] seeders seller réalistes
- [ ] runbook d’incident seller

## Critère de sortie
- [ ] la partie seller est défendable en production du point de vue qualité et support

---

## 15. Tests attendus

Le backend seller n’est pas considéré comme sérieux sans une vraie couverture de tests.

## 15.1 Tests minimums

### Auth / accès
- seller authentifié peut accéder à ses endpoints
- customer / driver ne peuvent pas accéder aux endpoints seller
- seller suspendu bloqué selon politique définie

### Ownership
- un seller ne peut pas lire/modifier le shop d’un autre
- un seller ne peut pas lire/modifier le produit d’un autre
- un seller ne peut pas lire une commande d’une autre boutique
- un seller ne peut pas poster dans une conversation étrangère

### Shop / KYC
- onboarding valide
- soumission review valide
- rejet si payload incomplet
- resoumission KYC possible selon règle retenue

### Produits
- création produit valide
- rejet validation sur données invalides
- impossible de soumettre sans prérequis
- impossible de publier si boutique/KYC non conformes
- gestion images et variantes contrôlée

### Orders
- listing seller limité à sa boutique
- `mark-ready` autorisé seulement sur statut compatible
- double action interdite / idempotente selon design

### Finance
- impossible de demander retrait sans KYC validé
- impossible de demander retrait > solde dispo
- impossible de dupliquer un retrait avec même clé

---

## 16. Observabilité et exploitation

Un backend pro doit être observable.

## À prévoir
- logs structurés sur actions seller importantes
- `request_id` par requête
- logs d’erreurs de validation d’upload
- logs des refus de policy critiques
- monitoring des jobs seller
- traçabilité des retraits et échecs payout
- dashboard minimal d’exploitation seller côté admin

---

## 17. Ce qui est explicitement hors périmètre de ce roadmap seller

Le présent document cible la **partie seller**. Il n’a pas vocation à détailler ici :

- l’intégralité du checkout customer
- le moteur complet de livraison driver
- l’intégralité du back-office finance global

En revanche, il décrit toutes les interfaces seller nécessaires avec ces domaines.

---

## 18. Définition du “done” pour la partie Seller

La partie seller backend sera considérée comme sérieuse quand les conditions suivantes seront vraies :

- [ ] un vendeur peut être onboardé proprement
- [ ] le KYC seller est géré de bout en bout
- [ ] la boutique possède un vrai cycle de vie
- [ ] le catalogue seller est robuste et modérable
- [ ] les uploads seller sont sécurisés
- [ ] les commandes seller sont consultables et traitables proprement
- [ ] la finance seller est lisible et cohérente
- [ ] les retraits seller sont encadrés, auditables et idempotents
- [ ] les APIs seller sont standardisées
- [ ] les permissions seller sont centralisées
- [ ] la traçabilité seller est présente
- [ ] les tests feature et de sécurité critiques sont en place

---

## 19. Priorité d’exécution recommandée

Ordre conseillé d’implémentation :

1. **Phase 0** — assainissement doc + base
2. **Phase 1** — sécurité / policies / audit / rate limits
3. **Phase 2** — shop + onboarding + KYC
4. **Phase 3** — products seller complets
5. **Phase 5** — orders seller
6. **Phase 6** — finance seller
7. **Phase 4** — dashboard seller fiable
8. **Phase 7** — chat / notifications
9. **Phase 8** — hardening final / docs / runbook

### Pourquoi cet ordre
Parce qu’un seller backend fiable dépend d’abord de :
- fondations claires
- sécurité
- identité / boutique / conformité
- catalogue propre
- commandes et argent seulement ensuite

---

## 20. Position finale

La partie seller ne doit pas être traitée comme un simple sous-module CRUD.

C’est un **back-office métier distribué**, exposé à des utilisateurs externes, manipulant :

- identité
- catalogue public
- opérations métier
- données sensibles
- argent

Donc le backend seller doit être construit avec des standards de production :

- architecture claire
- autorisation centralisée
- validation stricte
- audit trail
- idempotence sur le financier
- tests solides
- observabilité suffisante

Si ces points sont respectés, `marketify-sell` pourra être branché sur une API réellement professionnelle, et non sur un simple MVP fragile.
