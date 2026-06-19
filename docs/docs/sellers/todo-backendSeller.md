# TODO Backend Seller — Marketify

> **Projet :** `Marketify/m-back`
> **Périmètre :** backend seller restant à implémenter
> **Référence :** état réel du repo au 2026-06-17
> **But :** ne garder ici que le travail encore ouvert côté seller

---

## 1. État de référence

Les blocs suivants sont déjà livrés et ne font plus partie du TODO principal :

- hardening seller + policies + bootstrap + dashboard
- lifecycle shop seller de base
- KYC seller + review admin KYC
- finance seller + workflow admin withdrawals
- audit trail minimal + `request_id`
- workflow produit seller avec modération
- uploads médias produit privés + téléchargement contrôlé
- variantes produit + stock seller
- réordonnancement images produit
- fondations de `reserved_stock`
- lecture/détail seller orders + `mark-ready`

Ce document liste uniquement ce qui reste à faire pour considérer la partie seller comme réellement complète.

---

## 2. Priorité P0 — blocs encore structurants

### [ ] Intégration payout réelle

**Objectif :** sortir du retrait “interne” purement back-office et brancher un vrai provider.

#### À livrer
- [ ] service provider payout réel (Mobile Money / PSP)
- [ ] mapping propre `pending -> processing -> paid|failed|rejected`
- [ ] persistance des références provider
- [ ] callbacks/webhooks provider si le provider en impose
- [ ] idempotence complète côté provider + callbacks
- [ ] journalisation technique des échanges provider
- [ ] stratégie retry / failure / timeout
- [ ] tests de flux payout happy path + erreurs

#### Pourquoi c’est P0
Le module finance seller existe déjà, mais tant qu’il n’est pas branché à un payout réel, il manque la dernière étape métier importante.

---

### [ ] Flux commande seller plus complet

**Objectif :** dépasser `mark-ready` pour couvrir les actions seller réellement prévues.

#### À livrer
- [ ] `POST /api/v1/seller/orders/{order}/cancel-request`
- [ ] règles métier précises sur les cas d’annulation autorisés
- [ ] audit de la demande d’annulation seller
- [ ] exposition claire du statut d’annulation / review si nécessaire
- [ ] `GET /api/v1/seller/orders/{order}/packing-slip`
- [ ] génération d’un bon de préparation simple (JSON/HTML/PDF selon choix retenu)
- [ ] enrichissement du détail commande seller si des infos métier manquent encore

#### À vérifier ensuite
- [ ] faut-il exposer d’autres transitions seller encadrées que `mark-ready` ?
- [ ] faut-il distinguer explicitement `paid` et `preparing` côté seller ?

---

### [ ] Finaliser le workflow inventaire autour des commandes

**Objectif :** brancher complètement ce qui a déjà été posé pour `reserved_stock`.

#### Déjà posé
- réservation / libération / commit du stock
- support produit simple + variante
- tracking inventaire sur `orders`

#### Reste à brancher
- [ ] libération automatique de réservation sur annulation / paiement refusé
- [ ] commit automatique du stock au bon moment métier (`picked_up` ou autre règle retenue)
- [ ] choix métier explicite sur le moment exact du `commit`
- [ ] tests de transitions de commande avec impact stock
- [ ] audit trail des événements de réservation/libération/commit si retenu

#### Point de décision
- [ ] confirmer la règle officielle :
  - commit au paiement
  - commit au pickup
  - commit à la livraison

---

## 3. Priorité P1 — seller API encore incomplète

### [ ] Endpoint `GET /api/v1/seller/me`

**Objectif :** fournir le contexte seller dédié décrit dans la roadmap.

#### À livrer
- [ ] endpoint seller dédié
- [ ] user courant
- [ ] rôle
- [ ] état boutique
- [ ] état KYC
- [ ] permissions/capacités seller utiles au frontend

> Note : le bootstrap couvre déjà une partie du besoin, mais l’endpoint dédié reste absent.

---

### [ ] Uploads branding boutique

**Objectif :** compléter la partie settings/onboarding seller côté shop.

#### À livrer
- [ ] `POST /api/v1/seller/shop/logo`
- [ ] `POST /api/v1/seller/shop/banner`
- [ ] stockage privé ou contrôlé selon stratégie retenue
- [ ] validation MIME / taille / dimensions
- [ ] remplacement sécurisé de fichiers existants
- [ ] audit des modifications branding

---

### [ ] Endpoint onboarding seller explicite

**Objectif :** décider si l’on garde seulement `shops`/`shops/me`/`submit-review` ou si l’on expose un vrai endpoint onboarding orienté frontend.

#### À décider
- [ ] conserver la forme actuelle
- [ ] ou ajouter un alias/backing endpoint `POST /seller/onboarding`

#### Si retenu
- [ ] endpoint idempotent orienté multi-step onboarding
- [ ] agrégation shop + KYC + branding dans un seul flux

---

### [ ] KYC selfie optionnel si exigé métier

#### Constat actuel
- recto / verso : déjà gérés
- selfie : non implémenté

#### À livrer si retenu
- [ ] `POST /seller/kyc/selfie`
- [ ] stockage privé
- [ ] validation stricte
- [ ] exposition review admin cohérente

---

## 4. Priorité P1 — finance seller encore à compléter

### [ ] `GET /api/v1/seller/finance/transactions`

**Objectif :** fournir un historique orienté lecture, distinct du simple listing des retraits.

#### À livrer
- [ ] endpoint transactions seller
- [ ] lignes typées : ventes, commissions, retraits, ajustements si besoin
- [ ] pagination homogène
- [ ] filtres simples (`type`, `date_from`, `date_to`)
- [ ] contrat API clair pour le frontend finance

---

### [ ] Durcir encore les erreurs métier finance

#### À améliorer
- [ ] codes métier stables par cas (`INSUFFICIENT_AVAILABLE_BALANCE`, etc.) partout
- [ ] homogénéiser encore les `409` métier vs `422` validation
- [ ] standardiser la structure d’erreur sur tous les endpoints seller sensibles

---

## 5. Priorité P1 — produit/media encore à finir

### [ ] Limite métier du nombre d’images produit

#### À livrer
- [ ] plafond backend par produit
- [ ] validation dédiée sur upload
- [ ] message d’erreur métier clair

---

### [ ] Durcissement avancé des médias produit

#### À livrer
- [ ] cleanup des fichiers orphelins si besoin
- [ ] éventuelle image principale/couverture explicite
- [ ] contrôle plus fin des suppressions si une image minimale est requise
- [ ] optimisation/resize backend si retenu

---

### [ ] Listing seller products plus proche de la cible roadmap

#### Constat actuel
Le listing seller existe, mais la cible roadmap prévoit des filtres/tri plus riches.

#### À livrer
- [ ] filtres whitelistés : `status`, `category_id`, `q`, `stock_state`, `created_from`, `created_to`
- [ ] tris whitelistés : `created_at_desc`, `created_at_asc`, `updated_at_desc`, `price_asc`, `price_desc`, `stock_asc`, `stock_desc`
- [ ] tests de filtres/tri

---

## 6. Priorité P1 — shop lifecycle à compléter

### [ ] Workflow boutique avec rejet explicite

#### Constat actuel
Le shop gère aujourd’hui surtout :
- `draft`
- `pending`
- `active`
- `suspended`

La roadmap cible prévoit aussi un vrai `rejected` avec motif.

#### À livrer
- [ ] état `rejected` si confirmé métier
- [ ] motif de rejet boutique
- [ ] endpoint/admin workflow de rejet cohérent
- [ ] exposition seller du motif de rejet
- [ ] règles de resoumission après rejet

---

## 7. Priorité P2 — observabilité / sécurité / qualité

### [ ] Rate limits seller différenciés par famille d’endpoint

#### Constat actuel
Un throttling seller dédié existe, mais pas encore une granularité fine par type de route.

#### À livrer
- [ ] `bootstrap` dédié
- [ ] lecture produits
- [ ] mutation produits
- [ ] orders
- [ ] withdrawals très restrictif
- [ ] uploads plus bas
- [ ] futur chat message rate limit

---

### [ ] Audit trail élargi

#### Déjà audité
- settings
- withdrawals
- KYC
- reviews admin KYC
- produits
- reviews admin produit
- médias produit

#### Reste à auditer ou à renforcer
- [ ] transitions seller order supplémentaires
- [ ] actions d’inventaire si jugées sensibles
- [ ] branding boutique
- [ ] futures actions chat/notifications
- [ ] décisions admin boutique plus riches

---

### [ ] Standardisation complète du contrat d’erreur API

#### À livrer
- [ ] `code` stable sur tous les cas métier seller
- [ ] structure homogène sur 401/403/404/409/422/429
- [ ] suppression des écarts de formulation entre endpoints
- [ ] tests de contrat d’erreur sur endpoints critiques

---

## 8. Priorité P2 — fonctionnalités seller encore absentes

### [ ] Notifications seller

#### À livrer
- [ ] modèle/stockage notifications si retenu
- [ ] unread counts dans bootstrap si voulu
- [ ] événements : shop review, KYC review, product review, nouvelle commande, withdrawal update
- [ ] endpoint listing notifications seller
- [ ] endpoint mark-as-read / bulk read si nécessaire

---

### [ ] Chat seller

#### À livrer
- [ ] modèle conversations/messages
- [ ] policies de participation
- [ ] endpoints seller conversations/messages
- [ ] lien optionnel à la commande
- [ ] limites anti-abus / pièces jointes si retenues
- [ ] unread count éventuel dans bootstrap

---

## 9. Ordre recommandé des prochains lots

### Lot A — finance réel
- [ ] provider payout réel
- [ ] callbacks provider
- [ ] retries + traçabilité provider

### Lot B — commandes seller avancées
- [ ] cancel-request
- [ ] packing-slip
- [ ] brancher complètement release/commit inventory sur les transitions réelles

### Lot C — seller API polish
- [ ] `GET /seller/me`
- [ ] `GET /seller/finance/transactions`
- [ ] filtres/tri avancés produits
- [ ] branding shop uploads

### Lot D — plateforme seller complète
- [ ] notifications seller
- [ ] chat seller
- [ ] rate limits différenciés
- [ ] contrat d’erreurs finalisé

---

## 10. Définition de “backend seller complet”

On pourra considérer la partie seller comme vraiment complète quand les points suivants seront vrais :

- [ ] le seller peut aller du onboarding à l’exploitation sans trou fonctionnel majeur
- [ ] la finance seller est branchée à un payout réel
- [ ] les commandes seller couvrent les actions métier attendues
- [ ] l’inventaire est totalement branché au cycle de commande/paiement/livraison
- [ ] les uploads seller critiques sont tous sécurisés
- [ ] le contrat d’erreur est homogène
- [ ] l’observabilité seller est suffisante
- [ ] notifications et/ou chat sont livrés si l’app seller les dépend réellement
