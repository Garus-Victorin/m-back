# Marketify — Description Complète de l'Application

---

## 1. Qu'est-ce que Marketify ?

Marketify est une plateforme de commerce électronique multi-acteurs conçue spécifiquement pour le marché béninois. C'est un marketplace — c'est-à-dire un espace de vente en ligne où plusieurs vendeurs indépendants proposent leurs produits à des acheteurs, le tout orchestré par un opérateur central qui garantit la sécurité des transactions, la qualité des produits publiés et le bon déroulement des livraisons.

Concrètement, Marketify fonctionne comme un Jumia ou un Amazon local, mais pensé pour les contraintes réelles du Bénin : réseau mobile instable, paiement en Mobile Money (MTN MoMo, Moov Money), livraison par coursier indépendant, et utilisateurs peu familiers avec les interfaces complexes.

La plateforme repose sur un principe fondamental : **la confiance entre inconnus**. Un client ne connaît pas le vendeur. Un vendeur ne connaît pas le livreur. Marketify se positionne comme le tiers de confiance qui protège l'argent de l'acheteur jusqu'à la livraison confirmée, vérifie l'identité des vendeurs avant qu'ils publient leurs produits, et intervient en cas de litige. C'est ce mécanisme, appelé **escrow**, qui différencie Marketify d'un simple groupe WhatsApp de vente ou d'une page Facebook commerce.

---

## 2. Les quatre acteurs de la plateforme

Marketify est organisé autour de quatre rôles distincts, chacun disposant de sa propre interface dédiée.

### 2.1 L'Administrateur — Marketify Admin

L'administrateur est le propriétaire de la plateforme. Il ne vend rien et n'achète rien. Son rôle est de tout contrôler : valider les vendeurs qui veulent ouvrir une boutique, modérer les produits avant leur publication, surveiller les transactions financières, régler les litiges entre clients et vendeurs, et configurer les règles générales du système.

Concrètement, quand un commerçant soumet une demande pour ouvrir sa boutique sur Marketify, c'est l'administrateur qui examine les pièces d'identité fournies (KYC) et décide d'approuver ou de rejeter la demande. De même, chaque produit mis en vente passe d'abord par un écran de modération avant d'être visible par les acheteurs.

L'administrateur dispose également d'une vue financière complète : il voit en temps réel l'argent bloqué en escrow pour chaque commande en cours, peut déclencher les virements vers les vendeurs et les livreurs, et exporte les données comptables. En cas de dispute entre un client et un vendeur, il a accès à toutes les preuves uploadées et tranche la décision de remboursement.

Son interface est un panneau de gestion web accessible uniquement sur ordinateur de bureau, développé avec Laravel Filament, un outil qui génère automatiquement des interfaces d'administration professionnelles à partir du code métier Laravel.

### 2.2 Le Vendeur — Marketify Sell

Le vendeur est un commerçant béninois qui souhaite vendre ses produits en ligne sans créer son propre site web. Il peut s'agir d'un couturier, d'un revendeur d'électronique, d'un artisan, d'un marchand de vêtements, de chaussures, de produits alimentaires transformés, ou de tout autre type de bien physique.

Pour commencer à vendre, le commerçant crée son compte, remplit un formulaire d'inscription en plusieurs étapes : nom de sa boutique, catégorie d'activité, description, upload de son logo et de sa bannière, puis soumission de ses pièces KYC (pièce d'identité nationale et numéro de téléphone Mobile Money pour recevoir ses paiements). La boutique reste en statut « en attente de validation » jusqu'à ce que l'administrateur l'approuve.

Une fois validé, le vendeur peut publier ses produits. Pour chaque produit, il renseigne un titre, une description, un prix, un stock disponible, plusieurs photos, et des variantes si nécessaire (par exemple des tailles pour les vêtements, ou des couleurs). Il peut aussi programmer des promotions avec des dates de début et de fin.

Lorsqu'une commande arrive, le vendeur reçoit une notification. Il consulte le détail de la commande, prépare le colis, puis appuie sur « Prêt pour enlèvement » pour signaler qu'un livreur peut venir récupérer la marchandise. Il peut également communiquer directement avec le client via le chat intégré à la commande.

Le vendeur suit ses finances depuis un tableau de bord : il voit son chiffre d'affaires du jour, de la semaine, du mois, le montant de commissions prélevé par la plateforme, son solde disponible, et peut demander un retrait vers son compte MTN MoMo ou Moov Money.

Son interface est une application web progressive (PWA), accessible depuis un navigateur sur ordinateur ou tablette, développée en Angular 19 avec Angular Material. Elle est installable sur l'écran d'accueil comme une application native, et peut afficher les commandes du jour même sans connexion internet.

### 2.3 Le Client — Marketify Shop

Le client est l'acheteur. Il peut s'agir de n'importe quelle personne cherchant à acheter un produit en ligne avec livraison à domicile ou en point relais, en payant via son téléphone Mobile Money.

L'expérience client commence sur la page d'accueil de Marketify Shop, qui affiche des bannières promotionnelles, les catégories de produits disponibles, les articles populaires et les boutiques mises en avant. Le client peut naviguer par catégorie, utiliser la barre de recherche pour trouver un produit précis, et filtrer les résultats par prix, ville ou note des vendeurs.

Quand il trouve un produit qui l'intéresse, il ouvre la fiche produit : galerie de photos, description complète, prix, stock disponible, note et avis d'autres acheteurs, et profil du vendeur avec le lien vers sa boutique. Si le produit a des variantes (taille, couleur), il les sélectionne avant d'ajouter au panier.

Le panier persiste dans le navigateur même sans connexion. Quand le client est prêt à commander, il passe à la validation de commande en trois étapes : choix de l'adresse de livraison, choix du mode de livraison (à domicile avec calcul des frais selon la zone, ou point relais), puis paiement. Le paiement s'effectue via CinetPay, qui supporte MTN MoMo, Moov Money et les cartes bancaires.

Dès que le paiement est confirmé, l'argent est bloqué en escrow : il ne part pas immédiatement au vendeur. Il reste sous la garde de la plateforme jusqu'à ce que la livraison soit confirmée. Le client suit en temps réel le statut de sa commande — payée, préparée, en cours de livraison, livrée — et peut envoyer un message au vendeur ou au livreur depuis l'écran de suivi.

À la réception du colis, le livreur demande un code OTP à quatre chiffres que le client a reçu. Le client communique ce code, ce qui déclenche la confirmation de livraison. Si tout s'est bien passé, le client peut laisser un avis sur le produit et le vendeur. S'il y a un problème — colis endommagé, produit non conforme, non reçu — il peut ouvrir un litige depuis l'application et uploader des photos comme preuves.

Marketify Shop est une application web progressive optimisée pour mobile, développée en Angular 19 avec Tailwind CSS (sans Angular Material, pour rester sous 600 KB et charger rapidement sur une connexion 3G béninoise). Elle est installable depuis le navigateur et fonctionne partiellement hors ligne grâce au Service Worker qui met en cache les produits consultés et la page d'accueil.

### 2.4 Le Livreur — Marketify Go

Le livreur est un coursier indépendant qui s'inscrit sur Marketify pour effectuer des livraisons contre rémunération par course. Il peut être à moto, à vélo ou à pied selon les zones.

Après son inscription et validation KYC par l'administrateur, le livreur se connecte à son application et active son statut « En ligne ». Dès lors, la géolocalisation de son téléphone s'active en arrière-plan et transmet sa position au serveur toutes les cinq secondes. Les courses disponibles dans sa zone s'affichent sur une carte et dans une liste, chacune indiquant le point de récupération chez le vendeur, l'adresse de livraison chez le client, la distance et le montant qu'il va gagner.

Quand il accepte une course, le livreur se rend chez le vendeur. À son arrivée, il scanne un QR code affiché sur le bon de commande du vendeur — ce scan confirme qu'il est bien au bon endroit et déclenche le changement de statut de la commande vers « récupéré ». Il prend une photo du colis comme preuve.

Pendant la livraison, le client peut voir en temps réel la position du livreur sur une carte. Quand le livreur arrive à destination, il demande au client son code OTP. Le client lui donne le code à quatre chiffres reçu par SMS. Le livreur saisit ce code dans l'application. Si le code est correct, la livraison est confirmée et la commande passe au statut « complétée ». Ce mécanisme garantit que personne ne peut prétendre avoir livré sans l'accord du client.

Le livreur suit ses gains depuis un écran dédié : montant par course, cumul de la journée, de la semaine, et historique des virements reçus sur son Mobile Money. Les paiements se font chaque semaine automatiquement.

Marketify Go est une application Android native générée via Capacitor à partir d'une base Angular 19. L'utilisation de Capacitor est indispensable pour accéder aux fonctionnalités natives du téléphone : GPS en arrière-plan, caméra pour le scan QR et la photo de preuve, notifications push, et retour haptique sur chaque action critique.

---

## 3. Comment une transaction se déroule de bout en bout

Pour comprendre Marketify dans sa globalité, voici le déroulé complet d'une transaction, du premier clic à l'argent sur le compte du vendeur.

Un client ouvre Marketify Shop depuis son téléphone. Il cherche « chaussures homme pointure 42 », parcourt les résultats, sélectionne une paire à 15 000 FCFA chez un vendeur à Cotonou. Il l'ajoute au panier, indique son adresse à Fidjrossè, choisit la livraison à domicile pour 500 FCFA, et paie 15 500 FCFA via MTN MoMo. CinetPay débite son compte et notifie Marketify par webhook. L'argent est bloqué en escrow dans les comptes de la plateforme. La commande passe au statut « payée ».

Le vendeur reçoit immédiatement une notification sur Marketify Sell. Il voit la commande, prépare le colis avec le bon de commande imprimé qui comporte le QR code, puis appuie sur « Prêt pour enlèvement ». La commande passe au statut « prête ».

L'administrateur, depuis Marketify Admin, voit la commande prête et assigne un livreur disponible à proximité (pour le MVP, cette assignation est manuelle ; en V2 elle sera automatique selon la géolocalisation). Le livreur reçoit une notification sur Marketify Go.

Le livreur accepte la course. Il se rend chez le vendeur à Dantokpa, scanne le QR code. La commande passe à « récupérée ». Il prend la route vers Fidjrossè. Le client peut suivre sa progression en direct sur la carte.

Le livreur arrive à destination. Le client lui communique son code OTP à quatre chiffres reçu par SMS. Le livreur saisit le code. La commande passe à « complétée ».

48 heures après cette confirmation, si aucun litige n'est ouvert, un job automatique déclenche le virement : 14 200 FCFA au vendeur (15 000 FCFA moins 8% de commission soit 1 200 FCFA prélevés par la plateforme, soit 13 800 FCFA nets — plus précisément selon le calcul configuré) et 800 FCFA au livreur pour sa course. Les virements partent via l'API CinetPay Payout directement sur leurs comptes Mobile Money.

Si le client a un problème — il ouvre un litige. L'administrateur accède à la conversation tripartite (admin, client, vendeur), consulte les photos uploadées, et décide d'un remboursement total ou partiel. La plateforme retient les fonds en escrow jusqu'à la résolution.

---

## 4. Architecture technique

### 4.1 Vue d'ensemble

Marketify est construit comme un **monolithe modulaire** côté backend. Cela signifie qu'il n'y a qu'un seul serveur Laravel, mais organisé en modules internes indépendants qui ne se connaissent pas directement : le module Catalogue, le module Commande, le module Paiement, le module Livraison, le module Utilisateur, le module Notification. Ces modules communiquent uniquement via des événements Laravel : quand un paiement est confirmé, le module Paiement émet un événement `OrderPaid`, et chaque module qui a besoin de réagir (Commande qui change de statut, Notification qui envoie un SMS, Commission qui calcule le montant) écoute cet événement de façon indépendante. Aucun module ne va lire directement dans la base de données d'un autre.

Cette architecture permet de démarrer simplement avec un seul serveur pour le MVP, tout en gardant la possibilité de séparer les modules en microservices indépendants si le trafic l'exige en version 2.

Côté frontend, il y a quatre applications distinctes qui communiquent toutes avec la même API REST centrale :
- **Marketify Admin** : panneau Filament servi par Laravel lui-même
- **Marketify Sell** : application Angular 19 hébergée sur `sell.marketify.bj`
- **Marketify Shop** : application Angular 19 hébergée sur `marketify.bj`
- **Marketify Go** : application Angular 19 compilée en APK Android via Capacitor

### 4.2 Backend — Laravel 11

Le backend est développé en PHP 8.3 avec le framework Laravel 11, le framework PHP le plus populaire et le plus maintenu au monde. Il tourne avec Laravel Octane et le serveur Swoole, ce qui lui permet de rester en mémoire entre les requêtes et de traiter jusqu'à dix fois plus de requêtes par seconde qu'un serveur PHP classique.

La base de données principale est PostgreSQL 16, choisie pour sa robustesse, sa gestion native des champs JSON (utile pour stocker les adresses de livraison et les configurations), et sa fiabilité pour les transactions financières.

Redis est utilisé pour trois usages distincts : le cache des réponses API (les listes de produits mises en cache 5 minutes pour ne pas surcharger la base), la gestion des files de travail asynchrones (emails, SMS, calculs de commission envoyés en queue), et les sessions utilisateurs.

Meilisearch est un moteur de recherche full-text léger (50 Mo de RAM) qui permet une recherche instantanée sur les produits avec tolérance aux fautes de frappe. Pour le MVP, la recherche passe par SQL simple ; Meilisearch est activé en V2.

Laravel Reverb fournit les WebSockets pour le temps réel : notifications de nouvelles commandes, mises à jour de statut en direct, chat. Pour le MVP, le polling toutes les dix secondes est suffisant ; Reverb est activé en V2.

L'authentification repose sur Laravel Sanctum, qui génère des tokens sécurisés pour chaque application frontale (SPA web et APK mobile).

Le panneau d'administration est généré automatiquement par Laravel Filament v3, qui transforme les modèles de données en interfaces CRUD complètes avec filtres, recherche, pagination, export CSV, et actions personnalisées, sans qu'une seule ligne de HTML ou CSS soit nécessaire.

Les rôles et permissions sont gérés par Spatie Laravel Permission : chaque utilisateur a un rôle (admin, modérateur, finance, vendeur, client, livreur) et des permissions précises qui déterminent ce à quoi il a accès.

Chaque action administrative significative (validation de vendeur, rejet de produit, remboursement, changement de statut) est enregistrée dans un journal d'audit via Spatie Activitylog. Ce journal est consultable depuis le panneau admin et sert de preuve en cas de litige.

### 4.3 Frontends — Angular 19

Les trois applications frontales (Sell, Shop, Go) sont développées avec Angular 19 en mode Standalone Components — une architecture plus légère qui ne nécessite pas les modules NgModule d'Angular traditionnel. Le choix d'Angular pour toutes les applications (plutôt que Flutter pour Go et Shop comme envisagé initialement) est une décision d'efficacité d'équipe : une seule technologie à maîtriser, un seul écosystème, des bibliothèques partagées entre les applications via un monorepo.

**Marketify Sell** utilise Angular Material 3, le design system officiel de Google pour Angular. Il offre des composants prêts à l'emploi parfaitement adaptés aux formulaires complexes et aux tableaux de données : champs de saisie validés, sélecteurs, tables avec tri et filtre, dialogues modaux, navigation par onglets. Le vendeur n'a pas besoin d'un design spectaculaire mais d'une interface fiable et professionnelle pour gérer son stock.

**Marketify Shop** utilise Tailwind CSS et Angular CDK (Component Dev Kit) à la place d'Angular Material, délibérément plus léger. Angular Material ajoute environ 200 KB de CSS et JavaScript ; sur une connexion 3G béninoise, chaque kilobyte compte. Tailwind génère uniquement le CSS utilisé et Angular CDK fournit les comportements sans les styles. L'objectif est de rester sous 600 KB compressés pour que la page d'accueil charge en moins de 2,5 secondes sur une connexion 3G standard.

**Marketify Go** utilise Angular Material pour ses composants (boutons larges, formulaires simples) mais est principalement compilé en APK Android via Capacitor. Capacitor est un outil qui enveloppe une application web Angular dans une coque native Android, donnant accès à toutes les APIs du téléphone : géolocalisation en arrière-plan (indispensable pour tracker le livreur même quand l'application est fermée), caméra (pour scanner le QR code et prendre la photo de preuve), notifications push, retour haptique. Sans Capacitor, ces fonctionnalités seraient inaccessibles depuis un simple navigateur mobile.

Les trois applications partagent une bibliothèque commune (dans un monorepo) qui contient les interfaces TypeScript (Order, Product, User, etc.), les services API réutilisables, les guards d'authentification, les intercepteurs HTTP (qui ajoutent automatiquement le token Bearer à chaque requête), et les pipes utilitaires (formatage monétaire en FCFA, formatage de dates).

Chaque application est une Progressive Web App (PWA) : elle dispose d'un Service Worker qui met en cache les ressources statiques et certaines réponses API, ce qui lui permet de fonctionner partiellement sans connexion. Pour Marketify Shop, les produits consultés récemment et la page d'accueil sont disponibles hors ligne. Pour Marketify Sell, les commandes du jour restent accessibles. Pour Marketify Go, l'adresse et le téléphone du client en cours de livraison sont sauvegardés localement.

### 4.4 Paiement et Escrow

Le système de paiement repose entièrement sur CinetPay, un agrégateur de paiement africain qui centralise MTN MoMo, Moov Money et les cartes bancaires internationales dans une seule intégration.

Quand un client confirme sa commande, Marketify appelle l'API CinetPay pour créer une transaction et récupère une URL de paiement sécurisée. Le client est redirigé vers cette page pour entrer son numéro de téléphone et valider le paiement depuis son application Mobile Money. Une fois le paiement effectué, CinetPay envoie une notification automatique (webhook) au serveur Marketify, signée cryptographiquement avec HMAC-SHA256 pour prévenir toute falsification. Marketify vérifie cette signature, enregistre le paiement et change le statut de la commande.

L'argent encaissé ne transite pas dans les comptes bancaires des vendeurs immédiatement. Il est comptabilisé dans la base de données de Marketify comme « en escrow » — bloqué en attente de confirmation de livraison. Ce n'est qu'après la confirmation OTP et l'expiration du délai de litige de 48 heures que le virement est déclenché via l'API CinetPay Payout, qui envoie directement la somme sur le compte Mobile Money du vendeur et du livreur.

Tous les montants sont stockés en centimes entiers dans la base de données (jamais en décimaux) pour éviter les erreurs d'arrondi qui peuvent survenir avec les nombres à virgule flottante en informatique.

### 4.5 Base de données

La base de données PostgreSQL contient les tables suivantes :

La table **users** contient tous les comptes de la plateforme (admins, vendeurs, clients, livreurs) avec leur rôle, leur statut KYC et leur statut d'activation.

La table **shops** contient les boutiques des vendeurs avec leur nom, leur logo, leur description, leur statut (en attente, actif, suspendu), leur taux de commission personnalisé et leur solde disponible.

La table **categories** organise le catalogue en arborescence (catégories et sous-catégories).

Les tables **products**, **product_images** et **product_variants** contiennent les articles mis en vente avec leurs photos, variantes, prix et stock disponible. Le stock est géré en deux colonnes : le stock réel et le stock réservé (pour les commandes en cours de paiement).

Les tables **orders** et **order_items** enregistrent les commandes avec leur statut, l'adresse de livraison (en JSON), le type de livraison, le code OTP de confirmation, et les articles commandés avec le prix au moment de l'achat (pour éviter les problèmes si le vendeur change son prix après la commande).

La table **payments** enregistre chaque transaction CinetPay avec le canal utilisé, le montant, le statut et le payload brut du webhook.

La table **disputes** enregistre les litiges ouverts avec les motifs et la décision de résolution.

Les tables **conversations** et **messages** gèrent le chat entre clients, vendeurs et administrateurs, attaché à chaque commande.

La table **payouts** enregistre les virements effectués vers les vendeurs et les livreurs.

La table **settings** contient les paramètres configurables de la plateforme : taux de commission (8% par défaut), frais de livraison par zone, montant minimum de retrait (5 000 FCFA), délai avant payout automatique (48 heures).

### 4.6 Déploiement

Marketify est déployé sur un VPS (serveur privé virtuel) avec 2 processeurs virtuels et 4 Go de RAM, tournant sous Ubuntu 22.04. Hetzner et Oracle Cloud Free Tier sont les deux options envisagées. L'ensemble des services tourne dans des conteneurs Docker orchestrés par Docker Compose : le serveur PHP Octane, un worker de queue séparé, le serveur Reverb pour les WebSockets, PostgreSQL, Redis et Meilisearch.

Un reverse proxy Nginx reçoit toutes les requêtes HTTP/HTTPS entrantes et les distribue aux bons conteneurs selon le domaine ou le chemin. Le certificat SSL est géré automatiquement par Let's Encrypt.

Supervisor est utilisé pour garantir que les processus critiques (Octane, Queue Worker, Horizon) redémarrent automatiquement en cas de crash.

Le déploiement continu est géré par GitHub Actions : à chaque push sur la branche principale, le code est automatiquement déployé sur le serveur via SSH, les dépendances sont installées, les migrations de base de données sont jouées, les caches sont rechargés et Octane est rechargé sans interruption de service.

---

## 5. Ce qui différencie Marketify

### 5.1 L'escrow comme garantie de confiance

La majorité des ventes en ligne au Bénin se font via des groupes Facebook ou WhatsApp, sans aucune protection pour l'acheteur. Une fois l'argent envoyé, il n'y a aucun recours si le vendeur n'envoie pas le produit ou envoie quelque chose de non conforme. Marketify résout ce problème en bloquant systématiquement l'argent jusqu'à la confirmation de livraison par le client.

### 5.2 La vérification des vendeurs (KYC)

Tout vendeur souhaitant publier des produits doit soumettre ses documents d'identité. L'administrateur les vérifie manuellement avant d'activer la boutique. Ce processus élimine les vendeurs fantômes et les arnaques.

### 5.3 Le suivi GPS de la livraison

Contrairement aux marketplaces qui se contentent d'indiquer un statut texte (« en livraison »), Marketify Go transmet la position GPS du livreur en temps réel. Le client voit sur une carte où se trouve son colis à tout moment.

### 5.4 La confirmation OTP à la livraison

Le code OTP envoyé au client et saisi par le livreur à la livraison est un double mécanisme de sécurité : il prouve que la livraison a bien eu lieu physiquement (le livreur était au bon endroit avec le bon client), et il déclenche automatiquement le déblocage des fonds escrow.

### 5.5 Adapté aux contraintes locales

Chaque décision technique de Marketify tient compte du contexte béninois : le réseau mobile instable impose le mode hors ligne et le cache agressif ; la popularité du Mobile Money impose l'intégration CinetPay plutôt qu'une carte bancaire classique ; la diversité des appareils (téléphones Android bas de gamme) impose une application légère (600 KB) qui charge vite sur 3G ; la barrière linguistique impose une interface en français simple sans jargon technique.

---

## 6. Périmètre du MVP et évolutions prévues

La première version livrée (MVP) couvre le flux essentiel : un client peut trouver un produit, le payer, un vendeur peut le préparer, un livreur peut le récupérer et le livrer, l'admin peut superviser et payer les acteurs. Tout ce qui est nécessaire pour qu'une vraie transaction commerciale se déroule de bout en bout est présent.

Certaines fonctionnalités sont intentionnellement reportées à la version 2 pour tenir les délais : les WebSockets pour le temps réel (remplacés par un polling toutes les 10 secondes), l'assignation automatique des livreurs par algorithme géographique (remplacée par une assignation manuelle par l'admin), le moteur de recherche Meilisearch (remplacé par une recherche SQL basique), le payout automatique quotidien (remplacé par un déclenchement manuel depuis le panneau admin), et les litiges complexes avec chat tripartite (remplacés par un simple bouton d'annulation avant livraison).

Ces choix sont des décisions de priorité, pas des lacunes. Marketify V1 est un produit fonctionnel et viable. Marketify V2 est un produit complet et compétitif.
