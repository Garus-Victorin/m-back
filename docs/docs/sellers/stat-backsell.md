# Stat Backend Sell — Marketify

> **Projet :** `Marketify/m-back`  
> **Périmètre :** backend seller  
> **Dernière mise à jour :** 2026-06-17

---

## Introduction

Le backend seller de Marketify n’est plus un chantier théorique ni un simple assemblage de CRUD exposés à une interface vendeur. Il est devenu un sous-système métier identifiable, doté d’une vraie logique d’autorisation, de workflows explicites, d’un niveau de validation raisonnablement robuste, et d’un début d’ossature production-ready.

Autrement dit, la partie seller a quitté le stade du prototype. Elle sait désormais encadrer un vendeur, sa boutique, son KYC, son catalogue, sa finance, une partie de ses commandes, et la discipline nécessaire autour du stock, des médias et des audits.

Ce document n’est pas une roadmap. C’est une photographie rédigée de l’existant : ce qui est effectivement en place, ce qui fonctionne déjà, et ce qui reste encore à livrer pour fermer la boucle du backend seller.

---

## 1. Vue d’ensemble

Aujourd’hui, le backend seller repose sur une base bien plus sérieuse qu’au départ.

L’accès vendeur est durci. Les ressources critiques sont protégées par des policies. Le seller dispose d’un bootstrap backend, d’un dashboard, d’un workflow boutique, d’un workflow KYC, d’un workflow produit modéré, d’un espace finance avec demandes de retrait, et d’une première couche d’observabilité grâce à l’audit trail et au `request_id`.

Le périmètre seller couvre aussi désormais des sujets plus concrets et plus sensibles :

- la gestion des variantes produit ;
- la gestion du stock vendeur ;
- le réordonnancement des images produit ;
- les fondations du `reserved_stock` pour éviter qu’une commande ne contourne la vérité du stock ;
- la cohérence entre commande client, réservation d’inventaire et lecture seller.

Le backend seller n’est donc plus seulement “présent”. Il commence à raconter une logique métier cohérente.

---

## 2. Ce qui est réellement opérationnel

### 2.1 Accès, sécurité et cadre d’exécution seller

La première pierre posée a été la discipline d’accès.

Les routes seller sont aujourd’hui regroupées derrière un cadre commun : authentification Sanctum, middleware seller dédié, vérification d’activité du compte, ownership checks et throttling seller. Cela donne au module une frontière claire. Un vendeur inactif ou non autorisé ne traverse pas la couche HTTP comme si de rien n’était.

Les policies seller en place couvrent les objets essentiels du périmètre actuel :

- `Shop`
- `Product`
- `Order`
- `WithdrawalRequest`

Cette centralisation a une conséquence importante : le contrôle d’accès n’est plus uniquement disséminé dans les contrôleurs. Le module seller gagne ainsi en lisibilité, en cohérence et en maintenabilité.

---

### 2.2 Contexte seller : bootstrap et dashboard

Le seller dispose d’un point d’entrée backend crédible pour charger son application.

Le bootstrap seller et le dashboard seller existent déjà et fournissent les informations attendues pour faire vivre l’interface sans multiplier inutilement les appels.

Le backend sait calculer et renvoyer :

- le vendeur courant ;
- la boutique du vendeur ;
- l’état KYC ;
- des capacités UI utiles ;
- un résumé d’activité ;
- des commandes récentes ;
- une tendance de revenu sur 7 jours ;
- un `finance_summary` léger.

Le point important ici n’est pas seulement la présence des endpoints. C’est le fait que les métriques critiques viennent bien du serveur. Le frontend n’a plus à recomposer à l’aveugle des indicateurs métier fragiles à partir de listes partielles.

---

### 2.3 Cycle de vie boutique seller

Le seller peut créer sa boutique, la consulter, la mettre à jour, puis la soumettre explicitement à revue.

Le flux n’est plus implicite. Une boutique démarre en `draft`, puis peut être envoyée en `pending` via une action dédiée, avec des préconditions métier claires : complétude minimale du profil, existence d’un KYC, et absence de blocage évident.

Des timestamps de lifecycle ont été ajoutés pour donner au backend une mémoire exploitable :

- `submitted_at`
- `activated_at`
- `suspended_at`
- `suspension_reason`

Cela ne constitue pas encore un workflow boutique totalement finalisé au sens de la roadmap cible, mais la base actuelle est propre, lisible et déjà suffisante pour encadrer l’onboarding seller de manière sérieuse.

---

### 2.4 KYC seller et KYC admin

La partie KYC a franchi un cap important.

Côté seller, le backend gère :

- la lecture du dossier KYC courant ;
- la soumission et la resoumission ;
- les uploads privés des pièces ;
- le téléchargement contrôlé des documents ;
- la persistance des métadonnées KYC dans une table dédiée ;
- la synchronisation avec `users.kyc_status` et `users.kyc_document_url`.

Côté admin, le backend sait lister, consulter et reviewer les soumissions KYC seller, avec une transition bornée de `pending` vers `verified` ou `rejected`, et un rejet motivé obligatoire.

Le point décisif ici est la nature privée du stockage. Les fichiers KYC ne sont pas exposés comme des médias publics. Le backend les traite comme des documents sensibles, ce qui est exactement ce qu’il fallait faire.

---

### 2.5 Produits seller : de la création à la modération

Le module produit seller est désormais bien plus mature qu’un simple CRUD.

Le seller peut :

- lister ses produits ;
- créer un produit en `draft` ;
- consulter le détail d’un produit ;
- le modifier ;
- le soumettre à review ;
- l’archiver ;
- le restaurer ;
- gérer ses médias produit.

Le seller ne peut pas s’auto-publier. La publication reste encadrée par la modération backend. Le workflow produit a été aligné avec une logique de marketplace plus saine : le vendeur prépare, l’admin modère, le backend décide de la visibilité publique.

Côté admin, la revue produit permet déjà les décisions attendues :

- `approved`
- `rejected`
- `suspended`

Le backend public, de son côté, ne sert que les produits effectivement publiés, approuvés et actifs. Cela évite l’écueil classique où le catalogue public fuit des brouillons ou des états intermédiaires mal contrôlés.

---

### 2.6 Médias produit seller

Les médias produit ne sont plus un angle mort.

Le backend sait aujourd’hui :

- uploader des images produit en stockage privé ;
- les télécharger de manière contrôlée ;
- les supprimer ;
- les réordonner proprement.

Le réordonnancement des images existe maintenant côté seller, et la suppression recompresse les positions restantes pour garder une séquence cohérente. Le résultat est simple, mais solide : la galerie produit reste ordonnée et exploitable sans dérive technique.

Cette partie n’est pas encore totalement achevée au sens “production parfaite” — il reste des raffinements possibles comme un plafond métier, une image principale explicite ou un nettoyage avancé — mais le socle est réel et fonctionnel.

---

### 2.7 Variantes produit et gestion du stock seller

L’un des manques les plus structurants du backend seller a été comblé : le module produit sait désormais gérer des variantes.

Le backend supporte maintenant :

- une table `product_variants` ;
- le remplacement contrôlé des variantes d’un produit ;
- le stock et le stock réservé au niveau variante ;
- la synchronisation du stock agrégé au niveau produit ;
- la distinction entre produit simple et produit à variantes ;
- la réinitialisation prudente de certains états de modération quand un produit déjà approuvé est substantiellement remanié.

En pratique, cela signifie que le seller peut gérer un produit plus proche de la réalité terrain : taille, couleur, SKU dérivés, surcoût éventuel, et stock attaché à chaque combinaison.

Le backend prend aussi la bonne décision quand des variantes existent : le stock global n’est plus librement modifiable comme si le produit était simple. Il faut passer par la structure de variantes. C’est une contrainte saine, car elle protège l’intégrité du stock.

---

### 2.8 Finance seller

La finance seller n’est plus un écran vide ni une promesse de roadmap.

Le backend expose déjà :

- un résumé financier seller ;
- l’historique des retraits ;
- la création d’une demande de retrait ;
- les settings seller liés au payout.

Les règles d’éligibilité sont portées côté backend :

- boutique active ;
- KYC vérifié ;
- montant minimum ;
- solde disponible suffisant ;
- destination payout cohérente.

L’idempotence des demandes de retrait via `Idempotency-Key` est déjà en place, ce qui constitue un vrai signe de maturité sur un sujet sensible.

Côté admin, le traitement des retraits seller est borné et traçable, avec les transitions attendues, les informations de traitement, et le contexte nécessaire pour ne pas piloter les paiements à l’aveugle.

Le manque principal n’est plus l’existence du module finance. Le manque principal est désormais l’absence d’un vrai provider payout branché au monde réel.

---

### 2.9 Audit trail et corrélation de requêtes

Le backend seller conserve aujourd’hui une mémoire minimale de ses actes.

Une table `audit_logs` existe, et les actions les plus sensibles sont déjà journalisées :

- mises à jour settings seller ;
- créations de retrait ;
- traitements admin de retrait ;
- soumissions KYC ;
- reviews admin KYC ;
- créations et mises à jour produit ;
- soumissions produit ;
- reviews admin produit ;
- uploads et suppressions médias produit ;
- réordonnancement des images produit ;
- mises à jour de stock et variantes.

En parallèle, le middleware `AssignRequestId` donne aux requêtes API une identité technique exploitable. Le `request_id` remonte dans les réponses d’erreur et peut être persisté dans les audits. Cela améliore concrètement la capacité de diagnostiquer un comportement ou un incident.

---

### 2.10 Commandes seller

Le seller peut aujourd’hui lire ses commandes, consulter leur détail, et marquer une commande comme prête.

Le backend garantit déjà les points essentiels :

- un seller ne voit que les commandes de sa boutique ;
- les transitions sont contrôlées ;
- un statut incompatible déclenche un `409` métier ;
- les lectures seller sont orientées shop, pas marketplace globale.

Ce n’est pas encore le workflow commande seller complet décrit par la cible documentaire, mais le cœur de lecture et la première action métier seller sont présents.

---

## 3. Les évolutions récentes les plus importantes

Si l’on regarde le module seller comme une histoire de lots, les premiers blocs ont surtout installé l’ossature : sécurité, bootstrap, boutique, KYC, finance, modération produit.

Les évolutions plus récentes ont, elles, commencé à donner de la densité métier au système.

### Lot 7 — catalogue seller plus réaliste

Le backend seller sait désormais gérer :

- le détail produit seller ;
- le stock simple dédié ;
- les variantes produit ;
- le calcul de stock agrégé ;
- le `reserved_stock` comme donnée métier visible ;
- le réordonnancement des images.

Ce lot a marqué un tournant. Le catalogue seller n’est plus seulement modérable ; il devient exploitable dans des scénarios plus proches d’une marketplace réelle.

### Lot 8 — fondations inventaire / commande

Le backend a aussi commencé à traiter le stock comme une ressource à réserver, libérer et consommer selon le cycle de commande.

Des actions existent désormais pour :

- réserver l’inventaire d’une commande ;
- libérer une réservation ;
- committer définitivement le stock.

Le modèle `Order` garde maintenant la trace de ces événements via :

- `inventory_reserved_at`
- `inventory_committed_at`
- `inventory_released_at`

Même si cette logique n’est pas encore branchée partout dans tous les workflows futurs, elle change déjà la nature du backend : le stock seller commence à être gouverné par la commande, et non plus seulement édité depuis une fiche produit.

---

## 4. Ce qui reste encore ouvert

Le backend seller est sérieux, mais il n’est pas terminé.

Les principaux manques actuels sont les suivants.

### 4.1 Payout réel

Le module withdrawal existe, mais la sortie vers un provider réel reste à brancher. Tant que cette intégration n’existe pas, la finance seller reste incomplète au sens opérationnel.

### 4.2 Commandes seller avancées

Le seller peut lire et marquer “prêt”, mais il manque encore les actions décrites dans la cible :

- demande d’annulation seller ;
- bon de préparation / packing slip ;
- éventuelles autres transitions métiers encadrées.

### 4.3 Inventaire complètement relié au cycle de commande

La réservation, la libération et le commit existent en fondation, mais il reste à les brancher complètement au cycle réel : paiement, annulation, pickup, livraison, échec de paiement.

### 4.4 API seller encore incomplète sur certains points de confort

Certains endpoints cibles n’existent pas encore ou restent partiellement couverts :

- `GET /seller/me`
- `GET /seller/finance/transactions`
- uploads branding de boutique (`logo`, `banner`)
- éventuellement un vrai endpoint onboarding seller agrégé

### 4.5 Notifications seller et chat seller

Ces deux blocs restent absents. Ils figurent dans la cible produit, mais ne sont pas encore matérialisés côté backend.

### 4.6 Standardisation finale de la qualité API

Le backend seller dispose déjà d’un format de réponse convenable, de validations et de `request_id`, mais il reste encore une étape de polissage :

- codes métier stables partout ;
- rate limits différenciés par famille d’endpoint ;
- audit trail élargi ;
- homogénéisation complète des erreurs métier.

---

## 5. Évaluation d’ensemble

À ce stade, le backend seller de Marketify peut être décrit comme **fonctionnel, structuré et déjà crédible**, mais pas encore totalement clos.

Il sait porter un parcours vendeur substantiel :

- créer sa boutique ;
- compléter son KYC ;
- configurer ses paramètres de payout ;
- créer et modérer son catalogue ;
- manipuler variantes et stock ;
- consulter ses commandes ;
- suivre une partie de sa finance ;
- demander un retrait.

Ce qui manque désormais n’est plus le socle. Ce qui manque, ce sont les derniers blocs qui transforment un backend seller solide en backend seller entièrement abouti : paiement et payout réels, commandes seller plus riches, notifications, chat, et finitions de robustesse.

En d’autres termes :

le backend seller n’est plus en construction grossière ; il est dans sa phase de consolidation et d’achèvement.

---

## 6. Conclusion

Le backend seller de Marketify a déjà franchi l’essentiel du chemin structurel.

La sécurité de base existe. Les workflows critiques sont posés. Le catalogue seller est modérable. Le KYC est sérieux. La finance seller existe. Le stock n’est plus traité naïvement. Et le module commence à se comporter comme un vrai domaine métier, plutôt que comme une simple série de routes API.

Il reste encore des pièces importantes à livrer, mais elles s’inscrivent désormais dans un terrain beaucoup plus propre.

La bonne lecture de la situation est donc la suivante :

**la partie seller du backend est déjà solide ; ce qu’il reste à faire relève surtout de la finition stratégique, pas d’un sauvetage architectural.**
