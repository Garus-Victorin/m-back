# Paiement & Escrow — Marketify

## Fournisseurs intégrés

| Fournisseur | Usage | Zone |
|---|---|---|
| CinetPay | Paiement + Payout + SMS | Bénin, Côte d'Ivoire |
| MTN Mobile Money | Canal paiement via CinetPay | Bénin |
| Moov Money | Canal paiement via CinetPay | Bénin |
| Carte bancaire | Via CinetPay | International |

## Principe Escrow

L'argent ne va **jamais** directement au vendeur à l'achat. Il est bloqué côté plateforme jusqu'à confirmation de livraison.

```
Client paie 10 000 F
  → CinetPay reçoit → notifie webhook Marketify
    → Marketify stocke: escrow_balance += 10 000 F
      → Commande → paid

[Livraison confirmée OTP]
  → escrow_balance -= 10 000 F
  → seller_balance += 10 000 F - commission (ex: 8% = 800 F)
  → platform_balance += 800 F

[48h après livraison, si pas de litige]
  → Job: lancer virement CinetPay Payout API → compte Mobile Money vendeur
```

## Flux technique paiement

### 1. Initier paiement

```
POST /api/v1/payments/initiate
{
  "order_id": 123,
  "channel": "MTN"  // MTN | MOOV | CARD
}
```

Réponse :
```json
{
  "success": true,
  "data": {
    "payment_url": "https://secure.cinetpay.com/pay/...",
    "transaction_id": "TXN_ABC123"
  }
}
```

Frontend redirige (web) ou ouvre via `@capacitor/browser` (APK).

### 2. Webhook CinetPay

```
POST /api/webhooks/cinetpay
```

Vérifié par signature HMAC-SHA256 avec `CINETPAY_SECRET`.

Payload :
```json
{
  "cpm_trans_id": "TXN_ABC123",
  "cpm_result": "00",        // 00 = succès
  "cpm_amount": "10000",
  "cpm_currency": "XOF"
}
```

Handler `HandlePaymentWebhookAction` :
- `cpm_result === "00"` → émet `OrderPaid`
- Sinon → émet `OrderPaymentFailed`, libère stock réservé

### 3. Virement vendeur (Payout)

Déclenché par cron 48h après `completed` (si pas de litige ouvert) :

```php
// App\Modules\Payment\Jobs\PayoutSellerJob
CinetPayPayoutAPI::send([
    'receiver' => $seller->mobile_money_number,
    'amount'   => $earnedAmount,
    'currency' => 'XOF',
    'comment'  => "Marketify - Commande #{$order->id}"
]);
```

## Statuts paiement

| Statut | Description |
|---|---|
| `pending` | Commande créée, paiement non initié |
| `awaiting_payment` | URL générée, client en train de payer |
| `paid` | Webhook confirmé, argent en escrow |
| `failed` | Paiement refusé |
| `refunded` | Remboursement déclenché par admin |
| `paid_out` | Virement vendeur effectué |

## Remboursement (Litige)

Admin décide via Filament :
- **Remboursement total** → `refunded_amount = total_amount`
- **Remboursement partiel** → `refunded_amount < total_amount`

```
Admin décide remboursement 7 000 F sur 10 000 F
  → CinetPay Refund API (si canal supporte)
  → Ou: crédit sur wallet client Marketify
  → vendor_balance += 3 000 F - commission
```

## Sécurité

- Webhook validé par HMAC-SHA256
- Idempotence : `transaction_id` unique en base, double webhook ignoré
- Montants stockés en **centimes entiers** (pas de float)
- SSL obligatoire sur endpoint webhook
- Rate limiting sur `/api/payments/initiate` : 5 req/min par user
