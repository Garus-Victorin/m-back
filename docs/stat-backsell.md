# Stat Backend Sell — Marketify

> **Projet :** `Marketify/m-back`
> **Périmètre :** backend seller
> **Dernière mise à jour :** 2026-06-17

---

## 1. Vue d’ensemble

Le backend seller a maintenant dépassé le stade du simple CRUD boutique/produit.

Six lots successifs ont été réellement implémentés, corrigés et validés :

### Lot 1
- hardening accès seller
- policies d’ownership seller
- bootstrap seller
- dashboard seller
- seller orders lecture + détail + `mark-ready`

### Lot 2
- lifecycle boutique seller renforcé
- shop `draft -> pending review`
- KYC seller API
- soumission boutique à review sous conditions
- garde-fou produit : publication interdite si boutique non active
- enrichissement timestamps lifecycle boutique

### Lot 3
- finance seller summary
- historique seller withdrawals
- création de demandes de retrait seller
- idempotence via `Idempotency-Key`
- règles d’éligibilité retrait côté backend
- enrichissement du bootstrap seller avec `finance_summary`

### Lot 4
- payout settings seller dédiés
- workflow admin seller withdrawals
- transitions admin de retrait encadrées
- audit trail persistant minimal
- enrichissement des retraits avec contexte admin/shop/seller

### Lot 5
- workflow admin KYC seller
- uploads KYC privés réels
- téléchargement contrôlé des documents KYC
- audit des soumissions / reviews KYC
- request correlation id sur les APIs
- format d’erreur API enrichi avec `request_id`

### Lot 6
- workflow produit seller avec modération
- soumission produit seller à review
- review admin produit `approved|rejected|suspended`
- archivage logique produit
- restauration produit seller
- uploads médias produit privés
- téléchargement contrôlé des médias produit
- alignement catalogue public sur produits approuvés uniquement

---

## 2. État actuel du backend seller

## 2.1 Déjà opérationnel

### Accès et sécurité seller
- middleware `seller.active`
- throttling seller dédié
- contrôle seller centralisé sur les routes seller
- Policies seller de base pour `Shop`, `Product`, `Order`

### Shop seller
- création boutique seller
- lecture boutique courante
- mise à jour boutique
- statut initial en `draft`
- soumission boutique à review via endpoint dédié
- timestamps lifecycle boutique :
  - `submitted_at`
  - `activated_at`
  - `suspended_at`
  - `suspension_reason`

### KYC seller
- lecture KYC seller
- soumission / resoumission KYC seller
- uploads KYC privés via endpoints dédiés
- téléchargement contrôlé des fichiers KYC seller
- stockage des métadonnées KYC en table dédiée
- synchronisation état `users.kyc_status`
- synchronisation `users.kyc_document_url`
- validation stricte des chemins KYC privés appartenant au seller

### KYC admin seller
- `GET /api/v1/admin/kyc-submissions`
- `GET /api/v1/admin/kyc-submissions/{submission}`
- `PATCH /api/v1/admin/kyc-submissions/{submission}`
- `GET /api/v1/admin/kyc-submissions/{submission}/files/{side}`
- review admin bornée à `pending -> verified|rejected`
- rejet motivé obligatoire
- consultation enrichie avec `user`, `shop`, `reviewer`

### Produits seller
- listing seller
- création produit en `draft`
- mise à jour produit seller
- soumission produit à review
- archivage logique produit
- restauration produit seller
- uploads médias produit privés
- téléchargement contrôlé des médias produit
- ownership contrôlée
- impossibilité d’auto-publier côté seller
- modération produit via workflow backend

### Produits admin
- listing admin avec filtre `moderation_status`
- review admin produit
- téléchargement admin des médias produit
- archivage admin produit

### Dashboard seller
- endpoint de bootstrap seller
- endpoint dashboard seller
- résumé seller :
  - revenu du jour
  - commandes en attente
  - commandes prêtes
  - produits en rupture
  - produits publiés
  - total produits
- tendance revenu 7 jours
- commandes récentes
- `finance_summary` léger dans le bootstrap

### Finance seller
- `GET /seller/finance/summary`
- `GET /seller/finance/withdrawals`
- `POST /seller/finance/withdrawals`
- `GET /seller/settings`
- `PATCH /seller/settings`
- calcul des soldes côté backend
- demande de retrait avec idempotence
- règles d’éligibilité retrait
- destination payout dédiée côté boutique si configurée

### Finance admin seller
- `GET /api/v1/admin/finance/withdrawals`
- `GET /api/v1/admin/finance/withdrawals/{withdrawal}`
- `PATCH /api/v1/admin/finance/withdrawals/{withdrawal}`
- transitions `pending -> processing -> paid|failed`
- transition `pending -> rejected`
- `processed_by`, `processed_at`, `failure_reason`, `provider_reference`

### Audit trail
- table `audit_logs`
- audit des mises à jour de settings seller
- audit des créations de retrait seller
- audit des traitements admin de retrait seller
- audit des soumissions KYC seller
- audit des reviews admin KYC seller
- audit des créations / mises à jour produit seller
- audit des soumissions produit seller à review
- audit des reviews admin produit
- audit des uploads/suppressions médias produit

### Corrélation de requêtes
- middleware `AssignRequestId`
- propagation `X-Request-Id` sur réponses API
- `request_id` injecté dans les erreurs JSON API
- `request_id` persisté dans les audits quand disponibles

### Commandes seller
- listing seller orders
- détail seller order
- action `mark-ready`
- validation de transition de statut
- refus d’accès aux commandes d’une autre boutique

---

## 2.2 Encore à faire

Le backend seller n’est pas encore “complet production-ready”.

Les gros blocs encore ouverts sont :

- intégration provider payout réelle et callbacks éventuels
- notifications seller
- chat seller
- rate limits différenciés par famille d’endpoint
- audit trail encore à élargir à plus d’actions seller/admin
- durcissement supplémentaire du contrat d’erreurs API si standardisation complète voulue
- variantes produit / stock réservé / stock multi-variation
- réordonnancement médias produit et suppression sécurisée avancée

---

## 3. Implémentations livrées

## 3.1 Hardening seller

### Implémenté
- `EnsureActiveSeller`
- alias middleware `seller.active`
- named rate limiter `seller`

### Fichiers
- `app/Http/Middleware/EnsureActiveSeller.php`
- `bootstrap/app.php`
- `app/Providers/AppServiceProvider.php`
- `routes/api.php`

---

## 3.2 Policies seller

### Implémenté
- `ShopPolicy`
- `ProductPolicy`
- `OrderPolicy`
- activation `AuthorizesRequests`

### Fichiers
- `app/Policies/ShopPolicy.php`
- `app/Policies/ProductPolicy.php`
- `app/Policies/OrderPolicy.php`
- `app/Http/Controllers/Controller.php`

---

## 3.3 Bootstrap + dashboard seller

### Endpoints
- `GET /api/v1/seller/bootstrap`
- `GET /api/v1/seller/dashboard`

### Contenu
- seller courant
- shop seller
- `kyc_status`
- capacités UI côté backend
- résumé seller
- commandes récentes
- tendance revenu 7 jours

### Fichiers
- `app/Http/Controllers/Api/Seller/DashboardController.php`
- `app/Actions/Seller/GetSellerDashboardAction.php`

---

## 3.4 Seller orders

### Endpoints
- `GET /api/v1/seller/orders`
- `GET /api/v1/seller/orders/{order}`
- `POST /api/v1/seller/orders/{order}/mark-ready`

### Logique métier
- ownership seller contrôlée
- transition `mark-ready` centralisée
- conflit métier en `409` si statut incompatible

### Fichiers
- `app/Http/Controllers/Api/Seller/OrderController.php`
- `app/Actions/Seller/MarkSellerOrderReadyAction.php`
- `app/Models/Shop.php`

---

## 3.5 Lifecycle boutique seller

### Décision appliquée
La boutique seller ne naît plus directement en état de review implicite.

Elle naît maintenant en :
- `draft`

Puis peut être soumise explicitement en :
- `pending`

### Endpoint ajouté
- `POST /api/v1/seller/shops/submit-review`

### Règles métier
Soumission possible uniquement si :
- la boutique existe
- la boutique n’est pas déjà `active`
- la boutique n’est pas `suspended`
- le profil boutique est complet (`name`, `phone`, `email`, `address`, `city`)
- une soumission KYC existe
- la soumission KYC courante n’est pas `rejected`

### Timestamps lifecycle ajoutés
- `submitted_at`
- `activated_at`
- `suspended_at`
- `suspension_reason`

### Fichiers
- `database/migrations/2026_06_17_000106_enhance_shops_for_seller_lifecycle.php`
- `app/Actions/Seller/SubmitSellerShopForReviewAction.php`
- `app/Http/Controllers/Api/Seller/ShopController.php`
- `app/Http/Resources/ShopResource.php`
- `app/Http/Requests/Admin/UpdateShopRequest.php`
- `app/Http/Controllers/Api/Admin/ShopController.php`

---

## 3.6 KYC seller

### Table ajoutée
- `seller_kyc_submissions`

### Endpoint ajoutés
- `GET /api/v1/seller/kyc`
- `POST /api/v1/seller/kyc`

### Données gérées
- type de document
- numéro du document
- chemin recto
- chemin verso
- opérateur Mobile Money
- numéro Mobile Money
- notes
- statut KYC
- informations de review

### Comportement livré
- soumission KYC via `updateOrCreate`
- remise du statut à `pending`
- remise à zéro des informations de review
- mise à jour de `users.kyc_status`
- mise à jour de `users.kyc_document_url`

### Fichiers
- `database/migrations/2026_06_17_000107_create_seller_kyc_submissions_table.php`
- `app/Models/SellerKycSubmission.php`
- `database/factories/SellerKycSubmissionFactory.php`
- `app/Http/Requests/Seller/StoreSellerKycRequest.php`
- `app/Http/Resources/Seller/SellerKycSubmissionResource.php`
- `app/Actions/Seller/SubmitSellerKycAction.php`
- `app/Http/Controllers/Api/Seller/KycController.php`
- `app/Models/User.php`
- `app/Models/Shop.php`

---

## 3.7 Durcissement publication produit

### Règle livrée
Un seller ne peut pas créer ou mettre à jour un produit avec `status = published` si sa boutique n’est pas `active`.

### Fichier
- `app/Http/Controllers/Api/Seller/ProductController.php`

---

## 3.8 Finance seller

### Endpoints
- `GET /api/v1/seller/finance/summary`
- `GET /api/v1/seller/finance/withdrawals`
- `POST /api/v1/seller/finance/withdrawals`

### Comportement livré
- calcul backend des métriques seller finance
- `available_balance_cents`
- `pending_balance_cents`
- `total_earned_cents`
- `total_withdrawn_cents`
- `total_commissions_cents`
- `pending_withdrawals_cents`
- `min_withdrawal_cents`
- création de retrait seller avec `Idempotency-Key`
- rejet si boutique non active
- rejet si KYC non vérifié
- rejet si montant sous minimum
- rejet si solde disponible insuffisant
- réutilisation idempotente de la même requête si même clé + même payload
- rejet si même clé réutilisée pour un autre retrait

### Fichiers
- `database/migrations/2026_06_17_000108_create_seller_withdrawal_requests_table.php`
- `app/Models/SellerWithdrawalRequest.php`
- `database/factories/SellerWithdrawalRequestFactory.php`
- `app/Http/Requests/Seller/StoreSellerWithdrawalRequest.php`
- `app/Http/Resources/Seller/SellerWithdrawalRequestResource.php`
- `app/Actions/Seller/GetSellerFinanceSummaryAction.php`
- `app/Actions/Seller/CreateSellerWithdrawalRequestAction.php`
- `app/Http/Controllers/Api/Seller/FinanceController.php`
- `app/Policies/WithdrawalRequestPolicy.php`
- `routes/api.php`
- `app/Http/Controllers/Api/Seller/DashboardController.php`
- `app/Models/Shop.php`
- `app/Models/User.php`

---

## 3.9 Settings payout seller + audit trail

### Endpoints seller ajoutés
- `GET /api/v1/seller/settings`
- `PATCH /api/v1/seller/settings`

### Comportement livré
- stockage de payout settings dédiés au niveau shop
- `payout_beneficiary_name`
- `payout_mobile_money_provider`
- `payout_mobile_money_number`
- `payouts_enabled`
- validation backend de la cohérence de configuration payout
- audit persistant sur mise à jour des settings seller
- fallback de retrait sur le KYC si settings payout dédiés absents
- blocage des retraits si les payouts sont explicitement désactivés

### Fichiers
- `database/migrations/2026_06_17_000109_add_payout_settings_to_shops_table.php`
- `database/migrations/2026_06_17_000110_create_audit_logs_table.php`
- `app/Models/AuditLog.php`
- `app/Actions/Audit/RecordAuditLogAction.php`
- `app/Actions/Seller/UpdateSellerPayoutSettingsAction.php`
- `app/Http/Controllers/Api/Seller/SettingsController.php`
- `app/Http/Requests/Seller/UpdateSellerSettingsRequest.php`
- `app/Http/Resources/Seller/SellerSettingsResource.php`
- `app/Models/Shop.php`
- `routes/api.php`

---

## 3.10 Workflow admin seller withdrawals

### Endpoints admin ajoutés
- `GET /api/v1/admin/finance/withdrawals`
- `GET /api/v1/admin/finance/withdrawals/{withdrawal}`
- `PATCH /api/v1/admin/finance/withdrawals/{withdrawal}`

### Comportement livré
- listing admin paginé des retraits seller
- détail admin enrichi des retraits seller
- transitions admin strictement bornées
- refus des transitions invalides en `409`
- exigence `provider_reference` pour passage en `paid`
- exigence `failure_reason` pour passage en `failed` ou `rejected`
- journalisation persistante des traitements admin

### Fichiers
- `app/Actions/Admin/ProcessSellerWithdrawalAction.php`
- `app/Http/Controllers/Api/Admin/FinanceController.php`
- `app/Http/Requests/Admin/UpdateSellerWithdrawalRequest.php`
- `app/Http/Resources/Seller/SellerWithdrawalRequestResource.php`
- `routes/api.php`

---

## 3.11 Workflow admin KYC + uploads privés + request id

### Endpoints seller ajoutés
- `POST /api/v1/seller/kyc/uploads/front`
- `POST /api/v1/seller/kyc/uploads/back`
- `GET /api/v1/seller/kyc/files/front`
- `GET /api/v1/seller/kyc/files/back`

### Endpoints admin ajoutés
- `GET /api/v1/admin/kyc-submissions`
- `GET /api/v1/admin/kyc-submissions/{submission}`
- `PATCH /api/v1/admin/kyc-submissions/{submission}`
- `GET /api/v1/admin/kyc-submissions/{submission}/files/{side}`

### Comportement livré
- upload réel des pièces KYC sur stockage privé `local`
- chemins KYC strictement bornés au seller courant lors de la soumission
- téléchargement contrôlé seller/admin des documents privés
- review admin KYC autorisée uniquement sur soumission `pending`
- `rejection_reason` obligatoire pour un rejet KYC
- synchronisation `users.kyc_status` après review admin
- audit des soumissions KYC seller
- audit des reviews KYC admin
- middleware de corrélation `request_id`
- erreurs API JSON enrichies avec `meta.request_id`

### Fichiers
- `app/Http/Middleware/AssignRequestId.php`
- `bootstrap/app.php`
- `app/Actions/Seller/StoreSellerKycDocumentAction.php`
- `app/Actions/Seller/SubmitSellerKycAction.php`
- `app/Actions/Admin/ReviewSellerKycAction.php`
- `app/Http/Controllers/Api/Seller/KycController.php`
- `app/Http/Controllers/Api/Admin/KycController.php`
- `app/Http/Requests/Seller/StoreSellerKycDocumentRequest.php`
- `app/Http/Requests/Seller/StoreSellerKycRequest.php`
- `app/Http/Requests/Admin/ReviewSellerKycRequest.php`
- `app/Http/Resources/Seller/SellerKycSubmissionResource.php`
- `app/Actions/Audit/RecordAuditLogAction.php`
- `routes/api.php`

---

## 3.12 Workflow produit seller + médias privés + modération admin

### Endpoints seller ajoutés
- `POST /api/v1/seller/products/{product}/submit-review`
- `POST /api/v1/seller/products/{product}/archive`
- `POST /api/v1/seller/products/{product}/restore`
- `POST /api/v1/seller/products/{product}/images`
- `DELETE /api/v1/seller/products/{product}/images/{image}`
- `GET /api/v1/seller/products/{product}/images/{image}/download`

### Endpoints admin ajoutés
- `PATCH /api/v1/admin/products/{product}/review`
- `GET /api/v1/admin/products/{product}/images/{image}/download`
- filtre `moderation_status` sur `GET /api/v1/admin/products`

### Comportement livré
- création seller désormais en `draft`
- publication publique réservée aux produits `published` + `moderation_status = approved`
- soumission seller vers `pending_review`
- décision admin `approved|rejected|suspended`
- `rejected` renvoie le produit en `draft` avec motif
- `suspended` coupe la visibilité via `is_active = false`
- archivage logique seller/admin au lieu de suppression physique
- restauration seller d’un produit archivé vers `draft`
- uploads médias produit sur stockage privé `local`
- téléchargement contrôlé seller/admin des médias produit
- dashboard seller aligné sur produits réellement approuvés/publiés
- catalogue public aligné sur produits approuvés uniquement

### Fichiers
- `database/migrations/2026_06_17_000111_add_moderation_fields_to_products_table.php`
- `database/migrations/2026_06_17_000112_create_product_images_table.php`
- `app/Models/Product.php`
- `app/Models/ProductImage.php`
- `app/Http/Resources/ProductResource.php`
- `app/Http/Resources/ProductImageResource.php`
- `app/Http/Requests/Seller/StoreProductImageRequest.php`
- `app/Http/Requests/Admin/ReviewProductRequest.php`
- `app/Actions/Seller/StoreProductImageAction.php`
- `app/Actions/Seller/SubmitSellerProductForReviewAction.php`
- `app/Actions/Admin/ReviewSellerProductAction.php`
- `app/Http/Controllers/Api/Seller/ProductController.php`
- `app/Http/Controllers/Api/Admin/ProductController.php`
- `app/Http/Controllers/Api/ProductController.php`
- `app/Actions/Seller/GetSellerDashboardAction.php`
- `app/Policies/ProductPolicy.php`
- `routes/api.php`

---

## 3.13 Factories seller/orders ajoutées

### Ajoutées
- `DeliveryAddressFactory`
- `OrderFactory`
- `OrderItemFactory`
- `SellerKycSubmissionFactory`
- `SellerWithdrawalRequestFactory`

### Pourquoi
Permettre des tests feature sérieux côté seller sans bricolage de fixtures manuelles.

---

## 4. Tests ajoutés

## 4.1 Fichiers de tests seller
- `tests/Feature/Api/Seller/DashboardOrderTest.php`
- `tests/Feature/Api/Seller/KycLifecycleTest.php`
- `tests/Feature/Api/Seller/FinanceTest.php`
- `tests/Feature/Api/Seller/SettingsTest.php`
- `tests/Feature/Api/Admin/FinanceManagementTest.php`
- `tests/Feature/Api/Admin/KycManagementTest.php`
- `tests/Feature/Api/RequestIdTest.php`
- `tests/Feature/Api/Admin/ProductManagementTest.php`
- `tests/Feature/Api/Seller/ShopProductTest.php`
- `tests/Feature/Api/CatalogTest.php`
- `tests/Feature/Api/Seller/DashboardOrderTest.php`

## 4.2 Cas couverts

### Dashboard / bootstrap
- bootstrap seller
- dashboard seller
- capacités UI seller
- résumés et tendances

### Orders seller
- listing seller orders
- détail seller order
- `mark-ready`
- refus accès à une commande étrangère
- rejet transition métier invalide

### Shop / KYC lifecycle
- lecture KYC vide
- soumission KYC seller
- relecture KYC seller
- synchronisation état KYC user
- soumission boutique à review
- rejet soumission shop sans KYC
- rejet publication produit depuis boutique non active

### Finance seller
- lecture finance summary
- lecture historique des retraits
- création de retrait seller
- idempotence des demandes de retrait
- rejet sans `Idempotency-Key`
- rejet si solde insuffisant
- rejet si KYC non vérifié
- utilisation prioritaire des payout settings dédiés

### Settings seller
- lecture des payout settings
- mise à jour des payout settings
- audit persistant des settings seller

### Finance admin seller
- listing admin des retraits
- détail admin des retraits
- transitions admin valides `pending -> processing -> paid`
- rejet transition invalide
- interdiction d’accès aux non-admins
- audit persistant des traitements admin

### KYC seller/admin
- upload KYC privé seller
- soumission KYC seller avec chemins contrôlés
- téléchargement seller des documents KYC
- listing admin KYC
- détail admin KYC
- téléchargement admin des documents KYC
- review admin `verified` / `rejected`
- rejet si review invalide
- interdiction d’accès aux non-admins
- audit persistant des soumissions/reviews KYC

### Request correlation id
- préservation d’un `X-Request-Id` fourni côté client
- génération automatique sinon
- présence sur réponses API
- présence dans erreurs JSON API

### Produits seller/admin
- création seller en brouillon
- upload média produit privé
- soumission seller à review
- review admin produit
- archivage logique seller/admin
- restauration seller
- alignement catalogue public avec modération produit

---

## 5. Validation réellement exécutée

## 5.1 Diagnostics
Diagnostics ciblés + diagnostics projet global exécutés.

### Résultat
- **aucune erreur / aucun warning**

## 5.2 Tests seller ciblés — lot 1
Commande exécutée :
```bash
php m-back/vendor/bin/pest --configuration m-back/phpunit.xml m-back/tests/Feature/Api/Seller/ShopProductTest.php m-back/tests/Feature/Api/Seller/DashboardOrderTest.php
```

### Résultat
- **8 tests passés**
- **48 assertions**

## 5.3 Tests seller ciblés — lot 2
Commande exécutée :
```bash
php m-back/vendor/bin/pest --configuration m-back/phpunit.xml m-back/tests/Feature/Api/Seller/ShopProductTest.php m-back/tests/Feature/Api/Seller/DashboardOrderTest.php m-back/tests/Feature/Api/Seller/KycLifecycleTest.php
```

### Résultat
- **12 tests passés**
- **70 assertions**

## 5.4 Tests seller ciblés — lot 3
Commande exécutée :
```bash
php m-back/vendor/bin/pest --configuration m-back/phpunit.xml m-back/tests/Feature/Api/Seller/FinanceTest.php m-back/tests/Feature/Api/Seller/KycLifecycleTest.php m-back/tests/Feature/Api/Seller/DashboardOrderTest.php m-back/tests/Feature/Api/Seller/ShopProductTest.php
```

### Résultat
- **16 tests passés**
- **95 assertions**

## 5.5 Tests seller/admin finance + settings — lot 4
Commande exécutée :
```bash
php m-back/vendor/bin/pest --configuration m-back/phpunit.xml m-back/tests/Feature/Api/Seller/FinanceTest.php m-back/tests/Feature/Api/Seller/SettingsTest.php m-back/tests/Feature/Api/Admin/FinanceManagementTest.php
```

### Résultat
- **9 tests passés**
- **57 assertions**

## 5.6 Tests KYC admin + uploads privés + request id — lot 5
Commande exécutée :
```bash
php m-back/vendor/bin/pest --configuration m-back/phpunit.xml m-back/tests/Feature/Api/Seller/KycLifecycleTest.php m-back/tests/Feature/Api/Admin/KycManagementTest.php m-back/tests/Feature/Api/RequestIdTest.php
```

### Résultat
- **10 tests passés**
- **61 assertions**

## 5.7 Tests produit seller/admin + catalogue — lot 6
Commande exécutée :
```bash
php m-back/vendor/bin/pest --configuration m-back/phpunit.xml m-back/tests/Feature/Api/Seller/ShopProductTest.php m-back/tests/Feature/Api/Admin/ProductManagementTest.php m-back/tests/Feature/Api/CatalogTest.php m-back/tests/Feature/Api/Seller/DashboardOrderTest.php m-back/tests/Feature/Api/Seller/KycLifecycleTest.php
```

### Résultat
- **18 tests passés**
- **128 assertions**

## 5.8 Non-régression Feature globale
Commande exécutée :
```bash
php m-back/vendor/bin/pest --configuration m-back/phpunit.xml m-back/tests/Feature
```

### Résultat final
- **31 tests passés**
- **177 assertions**

---

## 6. Incident connu pendant validation

### Problème rencontré
`php artisan test` échoue dans ce contexte Windows à cause d’un problème Symfony Process / fichier temporaire.

### Contournement retenu
Validation exécutée via :
```bash
php m-back/vendor/bin/pest --configuration m-back/phpunit.xml ...
```

### Conclusion
Le problème est lié à l’environnement d’exécution des tests, pas au comportement fonctionnel des implémentations seller livrées.

---

## 7. Évaluation d’ingénierie

Le backend seller a maintenant :

- une garde d’accès centralisée
- une base d’authorization saine
- un bootstrap exploitable pour l’app sell
- un dashboard seller back-driven
- un premier workflow commandes seller sérieux
- une première vraie notion de lifecycle boutique
- un premier module KYC seller exploitable
- un premier module finance seller exploitable
- une validation automatisée correcte

Ce n’est toujours pas la version finale du backend seller, mais c’est désormais une base beaucoup plus propre, plus crédible et plus défendable techniquement.

---

## 8. Prochaine étape recommandée

### Priorité suivante
**Variantes produit + stock réservé + gestion de stock plus réaliste**

### Pourquoi
Le produit a maintenant un vrai cycle de modération et des médias privés, mais le catalogue seller reste encore mono-variation et la logique de stock reste trop simple pour une marketplace sérieuse :
- pas de variantes produit structurées
- pas de stock réservé
- pas de distinction claire entre stock vendu / réservé / disponible
- pas de workflow média avancé (réordonnancement, image principale)

### Sous-lot recommandé
1. ajouter table de variantes produit
2. gérer SKU par variante si nécessaire
3. introduire `reserved_stock` et calcul de stock disponible
4. empêcher survente via réservation cohérente côté commandes
5. ajouter endpoints seller de gestion variantes/stock
6. permettre réordonnancement des images produit
7. ajouter tests feature sur stock et variantes
