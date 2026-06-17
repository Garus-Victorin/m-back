# Base de données — Marketify

**SGBD :** mysql
**Toutes les clés étrangères sont indexées. Tous les montants en centimes (entiers).**

---

## Tables principales

### users
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name | varchar(100) | |
| email | varchar(150) unique | |
| phone | varchar(20) unique | |
| password | varchar | bcrypt |
| role | enum | admin, moderator, finance, seller, customer, driver |
| kyc_status | enum | pending, verified, rejected |
| kyc_document_url | varchar | |
| is_active | boolean | default true |
| created_at / updated_at | timestamp | |

### shops (vendeurs)
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| user_id | bigint FK users | |
| name | varchar(100) | |
| slug | varchar unique | |
| logo_url | varchar | |
| banner_url | varchar | |
| description | text | |
| status | enum | pending, active, suspended |
| commission_rate | decimal(5,2) | override taux plateforme si besoin |
| balance_cents | bigint | solde disponible en centimes |
| created_at / updated_at | timestamp | |

### categories
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| parent_id | bigint FK nullable | sous-catégorie |
| name | varchar(100) | |
| slug | varchar unique | |
| icon_url | varchar | |

### products
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| shop_id | bigint FK shops | |
| category_id | bigint FK categories | |
| title | varchar(200) | |
| slug | varchar unique | |
| description | text | |
| price_cents | bigint | |
| compare_price_cents | bigint nullable | prix barré |
| stock | int | |
| reserved_stock | int | stock réservé commandes en cours |
| status | enum | pending, active, rejected, archived |
| rejection_reason | text nullable | |
| created_at / updated_at | timestamp | |

### product_images
| Colonne | Type | |
|---|---|---|
| id | bigint PK | |
| product_id | bigint FK | |
| url | varchar | |
| position | tinyint | ordre affichage |

### product_variants
| Colonne | Type | |
|---|---|---|
| id | bigint PK | |
| product_id | bigint FK | |
| name | varchar(50) | ex: "Taille", "Couleur" |
| value | varchar(50) | ex: "XL", "Rouge" |
| extra_price_cents | int | surcoût variant |
| stock | int | |

### orders
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| customer_id | bigint FK users | |
| shop_id | bigint FK shops | |
| driver_id | bigint FK users nullable | |
| status | enum | pending, paid, ready, picked, in_delivery, completed, disputed, cancelled |
| total_cents | bigint | |
| delivery_fee_cents | bigint | |
| commission_cents | bigint | |
| delivery_address | jsonb | {street, city, district, lat, lng} |
| delivery_type | enum | home, relay |
| otp_code | varchar(4) | pour confirmation livraison |
| otp_expires_at | timestamp | |
| completed_at | timestamp nullable | |
| payout_at | timestamp nullable | date virement vendeur planifié |
| created_at / updated_at | timestamp | |

### order_items
| Colonne | Type | |
|---|---|---|
| id | bigint PK | |
| order_id | bigint FK | |
| product_id | bigint FK | |
| variant_id | bigint FK nullable | |
| quantity | int | |
| unit_price_cents | bigint | prix au moment de l'achat |

### payments
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| order_id | bigint FK unique | |
| transaction_id | varchar unique | ID CinetPay |
| channel | enum | MTN, MOOV, CARD |
| amount_cents | bigint | |
| status | enum | pending, awaiting_payment, paid, failed, refunded, paid_out |
| webhook_payload | jsonb | payload brut CinetPay |
| paid_at | timestamp nullable | |
| created_at / updated_at | timestamp | |

### disputes
| Colonne | Type | |
|---|---|---|
| id | bigint PK | |
| order_id | bigint FK unique | |
| opened_by | bigint FK users | |
| reason | text | |
| status | enum | open, admin_review, resolved |
| resolution | text nullable | |
| refund_amount_cents | bigint nullable | |
| resolved_at | timestamp nullable | |

### messages
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| conversation_id | bigint FK | |
| sender_id | bigint FK users | |
| body | text | |
| attachment_url | varchar nullable | |
| read_at | timestamp nullable | |
| created_at | timestamp | |

### conversations
| Colonne | Type | Notes |
|---|---|---|
| id | bigint PK | |
| order_id | bigint FK nullable | |
| type | enum | customer_seller, admin_dispute |
| participants | jsonb | [user_ids] |
| created_at | timestamp | |

### payouts
| Colonne | Type | |
|---|---|---|
| id | bigint PK | |
| shop_id | bigint FK nullable | |
| driver_id | bigint FK nullable | |
| amount_cents | bigint | |
| mobile_money_number | varchar | |
| status | enum | pending, sent, failed |
| cinetpay_ref | varchar nullable | |
| sent_at | timestamp nullable | |

### settings
| Colonne | Type | |
|---|---|---|
| key | varchar PK | |
| value | text | |
| updated_at | timestamp | |

Clés importantes :
- `platform_commission_rate` → "8.00"
- `delivery_fee_cotonou` → "500"
- `payout_delay_hours` → "48"
- `min_withdrawal_cents` → "500000" (5 000 F)

---

## Index recommandés

```sql
CREATE INDEX idx_products_status ON products(status);
CREATE INDEX idx_products_shop ON products(shop_id);
CREATE INDEX idx_orders_customer ON orders(customer_id);
CREATE INDEX idx_orders_shop ON orders(shop_id);
CREATE INDEX idx_orders_driver ON orders(driver_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_payments_transaction ON payments(transaction_id);
CREATE INDEX idx_messages_conversation ON messages(conversation_id);
```
