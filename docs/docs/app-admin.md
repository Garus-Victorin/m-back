# App Admin — Marketify Admin

**Stack :** Laravel Filament v3 (zéro frontend Angular/Flutter)
**Cible :** Desktop uniquement, WiFi bureau

## Rôles accédant à cette interface

| Rôle | Accès |
|---|---|
| `admin` | Tout |
| `moderator` | Vendeurs, Produits, Litiges |
| `finance` | Finance, Virements |

## Resources Filament

### UserResource

Pages : Liste, Détail
Actions :
- `VerifySellerAction` → passe seller en `verified`, émet `SellerVerified`
- `SuspendUserAction` → suspend + invalide tokens
- `ChangeRoleAction` → via Spatie Permission

Filtres : par rôle, par statut KYC, par date inscription

### ProductResource

Pages : Liste (filtre `pending`), Détail avec preview
Actions :
- `ApproveProductAction` → status `active`, notifie vendeur
- `RejectProductAction` → motif requis, notifie vendeur

### OrderResource

Pages : Liste toutes commandes, Détail timeline statuts
Actions :
- `ForceStatusAction` → admin force statut manuellement
- `InitiateRefundAction` → appelle `ProcessRefundAction`, émet `OrderRefunded`

### DisputeResource

Pages : Liste litiges ouverts, Vue tripartite (chat admin + client + vendeur)
Actions :
- Upload preuves (photos)
- `ResolveDisputeAction` → décision + remboursement partiel ou total

### PayoutResource

Pages : Liste virements en attente
Actions :
- `LaunchPayoutAction` → appelle CinetPay Payout API
- `BatchPayoutAction` → virement batch tous vendeurs éligibles

### AnalyticsPage (Page custom)

Widgets :
- CA total / jour / semaine / mois (Chart.js via Filament)
- Commandes par statut
- Top 10 vendeurs
- Zones de livraison chaudes

### SettingsPage (Page custom)

Champs éditables sans redéploiement :
- Taux commission plateforme (%)
- Frais livraison par zone (JSON structuré)
- Activer/désactiver CinetPay, MTN MoMo, Moov Money
- Montant minimum retrait vendeur

## Audit

Tous les changements loggés via `spatie/laravel-activitylog`.
Table `activity_log` consultable dans Filament via `ActivityResource`.

## Avantage

Filament génère l'interface complète (CRUD, filtres, exports CSV, pagination serveur) en 3 jours. Équivalent Angular = 3 semaines.
