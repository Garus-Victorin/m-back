# API Seller - Marketify

## Introduction

Cette documentation décrit l'API complète pour la partie seller de Marketify. Toutes les routes sont protégées par authentification Sanctum et nécessitent un utilisateur avec le rôle `seller`.

## Authentification

### Obtenir un token

```bash
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "seller@example.com",
  "password": "yourpassword"
}
```

**Réponse:**
```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "token": "your-sanctum-token",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "seller@example.com",
      "role": "seller"
    }
  }
}
```

### Utiliser le token

Ajoutez le token à l'en-tête `Authorization`:
```bash
Authorization: Bearer your-sanctum-token
```

## Informations Seller

### Récupérer les informations seller

```bash
GET /api/v1/seller/me
Authorization: Bearer your-sanctum-token
```

**Réponse:**
```json
{
  "success": true,
  "message": "Seller information retrieved successfully.",
  "data": {
    "seller": {
      "id": 1,
      "name": "John Doe",
      "email": "seller@example.com",
      "phone": "+22912345678",
      "role": "seller",
      "is_active": true,
      "kyc_status": "verified",
      "created_at": "2023-01-01T00:00:00.000000Z",
      "updated_at": "2023-01-01T00:00:00.000000Z"
    },
    "shop": {
      "id": 1,
      "name": "John's Shop",
      "status": "active",
      "logo_url": null,
      "banner_url": null
    },
    "capabilities": {
      "can_create_products": true,
      "can_manage_orders": true,
      "can_request_withdrawals": true,
      "can_upload_media": true,
      "can_manage_shop_settings": true,
      "can_view_finance": true,
      "can_view_dashboard": true
    }
  }
}
```

## Boutique (Shop)

### Créer une boutique

```bash
POST /api/v1/seller/shops
Authorization: Bearer your-sanctum-token
Content-Type: application/json

{
  "name": "My Shop",
  "description": "Best products in town",
  "phone": "+22912345678",
  "address": "123 Main St",
  "city": "Cotonou",
  "country": "Benin"
}
```

**Réponse:**
```json
{
  "success": true,
  "message": "Shop created successfully.",
  "data": {
    "shop": {
      "id": 1,
      "name": "My Shop",
      "description": "Best products in town",
      "status": "draft",
      "created_at": "2023-01-01T00:00:00.000000Z"
    }
  }
}
```

### Mettre à jour une boutique

```bash
PATCH /api/v1/seller/shops/{shop}
Authorization: Bearer your-sanctum-token
Content-Type: application/json

{
  "name": "Updated Shop Name",
  "description": "Updated description"
}
```

**Réponse:**
```json
{
  "success": true,
  "message": "Shop updated successfully.",
  "data": {
    "shop": {
      "id": 1,
      "name": "Updated Shop Name",
      "description": "Updated description",
      "status": "draft"
    }
  }
}
```

### Soumettre une boutique pour revue

```bash
POST /api/v1/seller/shops/submit-review
Authorization: Bearer your-sanctum-token
```

**Réponse:**
```json
{
  "success": true,
  "message": "Shop submitted for review successfully.",
  "data": {
    "shop": {
      "id": 1,
      "status": "pending",
      "submitted_for_review_at": "2023-01-01T00:00:00.000000Z"
    }
  }
}
```

### Upload du logo de la boutique

```bash
POST /api/v1/seller/shops/logo
Authorization: Bearer your-sanctum-token
Content-Type: multipart/form-data

file: [Binary image file]
```

**Réponse:**
```json
{
  "success": true,
  "message": "Shop logo uploaded successfully.",
  "data": {
    "branding": {
      "logo_url": "/storage/shops/1/branding/logo.jpg",
      "banner_url": null,
      "has_logo": true,
      "has_banner": false
    }
  }
}
```

### Upload de la bannière de la boutique

```bash
POST /api/v1/seller/shops/banner
Authorization: Bearer your-sanctum-token
Content-Type: multipart/form-data

banner: [Binary image file]
```

**Réponse:**
```json
{
  "success": true,
  "message": "Shop banner uploaded successfully.",
  "data": {
    "branding": {
      "logo_url": null,
      "banner_url": "/storage/shops/1/branding/banner.jpg",
      "has_logo": false,
      "has_banner": true
    }
  }
}
```

### Récupérer le branding de la boutique

```bash
GET /api/v1/seller/shops/branding
Authorization: Bearer your-sanctum-token
```

**Réponse:**
```json
{
  "success": true,
  "message": "Shop branding retrieved successfully.",
  "data": {
    "branding": {
      "logo_url": "/storage/shops/1/branding/logo.jpg",
      "banner_url": "/storage/shops/1/branding/banner.jpg",
      "has_logo": true,
      "has_banner": true
    }
  }
}
```

## Onboarding Seller

### Récupérer le statut d'onboarding

```bash
GET /api/v1/seller/onboarding
Authorization: Bearer your-sanctum-token
```

**Réponse:**
```json
{
  "success": true,
  "message": "Seller onboarding status retrieved successfully.",
  "data": {
    "onboarding": {
      "user_id": 1,
      "email": "seller@example.com",
      "kyc_status": "verified",
      "kyc_submitted_at": "2023-01-01T00:00:00.000000Z"
    },
    "shop": {
      "id": 1,
      "name": "My Shop",
      "status": "active"
    }
  }
}
```

### Compléter l'onboarding

```bash
POST /api/v1/seller/onboarding
Authorization: Bearer your-sanctum-token
Content-Type: multipart/form-data

{
  "shop": {
    "name": "My Shop",
    "description": "Best products",
    "phone": "+22912345678",
    "address": "123 Main St",
    "city": "Cotonou",
    "country": "Benin"
  },
  "payout": {
    "provider": "MTN",
    "number": "22912345678"
  },
  "kyc": {
    "document_type": "id_card",
    "document_front": [Binary file],
    "document_back": [Binary file]
  }
}
```

**Réponse:**
```json
{
  "success": true,
  "message": "Seller onboarding completed successfully. Your shop and KYC are under review.",
  "data": {
    "shop_status": "pending",
    "kyc_status": "pending",
    "next_steps": [
      "Wait for admin review (usually 24-48 hours)",
      "You will receive a notification once your shop is approved",
      "Check your email for updates"
    ]
  }
}
```

## Produits

### Lister les produits

```bash
GET /api/v1/seller/products
Authorization: Bearer your-sanctum-token
```

**Paramètres optionnels:**
- `status`: Filtrer par statut (draft,pending,approved,etc.)
- `category_id`: Filtrer par catégorie
- `q`: Recherche par nom/description
- `stock_state`: Filtrer par état de stock (in_stock,out_of_stock,low_stock)
- `created_from`: Date de création minimum
- `created_to`: Date de création maximum
- `sort`: Tri (created_at_desc,price_asc,etc.)
- `per_page`: Nombre d'éléments par page

**Réponse:**
```json
{
  "success": true,
  "message": "Seller products retrieved successfully.",
  "data": {
    "products": [
      {
        "id": 1,
        "name": "Product Name",
        "price": 1000,
        "stock": 10,
        "status": "approved",
        "images": []
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 1
    },
    "filters": {
      "status": null,
      "category_id": null,
      "q": null,
      "stock_state": null,
      "created_from": null,
      "created_to": null
    },
    "sort": "created_at_desc"
  }
}
```

### Créer un produit

```bash
POST /api/v1/seller/products
Authorization: Bearer your-sanctum-token
Content-Type: application/json

{
  "name": "New Product",
  "price": 1000,
  "stock": 10,
  "category_id": 1,
  "description": "Product description"
}
```

**Réponse:**
```json
{
  "success": true,
  "message": "Product created successfully.",
  "data": {
    "product": {
      "id": 1,
      "name": "New Product",
      "price": 1000,
      "stock": 10,
      "status": "draft"
    }
  }
}
```

### Récupérer un produit

```bash
GET /api/v1/seller/products/{product}
Authorization: Bearer your-sanctum-token
```

**Réponse:**
```json
{
  "success": true,
  "message": "Seller product retrieved successfully.",
  "data": {
    "product": {
      "id": 1,
      "name": "Product Name",
      "price": 1000,
      "stock": 10,
      "status": "approved",
      "images": []
    }
  }
}
```

### Mettre à jour un produit

```bash
PATCH /api/v1/seller/products/{product}
Authorization: Bearer your-sanctum-token
Content-Type: application/json

{
  "name": "Updated Product",
  "price": 1200
}
```

**Réponse:**
```json
{
  "success": true,
  "message": "Product updated successfully.",
  "data": {
    "product": {
      "id": 1,
      "name": "Updated Product",
      "price": 1200,
      "status": "draft"
    }
  }
}
```

### Mettre à jour le stock

```bash
PATCH /api/v1/seller/products/{product}/stock
Authorization: Bearer your-sanctum-token
Content-Type: application/json

{
  "stock": 20
}
```

**Réponse:**
```json
{
  "success": true,
  "message": "Product stock updated successfully.",
  "data": {
    "product": {
      "id": 1,
      "stock": 20
    }
  }
}
```

### Soumettre un produit pour revue

```bash
POST /api/v1/seller/products/{product}/submit-review
Authorization: Bearer your-sanctum-token
```

**Réponse:**
```json
{
  "success": true,
  "message": "Product submitted for review successfully.",
  "data": {
    "product": {
      "id": 1,
      "status": "pending",
      "moderation_status": "pending"
    }
  }
}
```

### Archiver un produit

```bash
POST /api/v1/seller/products/{product}/archive
Authorization: Bearer your-sanctum-token
```

**Réponse:**
```json
{
  "success": true,
  "message": "Product archived successfully.",
  "data": {
    "product": {
      "id": 1,
      "status": "archived"
    }
  }
}
```

### Restaurer un produit

```bash
POST /api/v1/seller/products/{product}/restore
Authorization: Bearer your-sanctum-token
```

**Réponse:**
```json
{
  "success": true,
  "message": "Product restored successfully.",
  "data": {
    "product": {
      "id": 1,
      "status": "draft"
    }
  }
}
```

### Supprimer un produit

```bash
DELETE /api/v1/seller/products/{product}
Authorization: Bearer your-sanctum-token
```

**Réponse:**
```json
{
  "success": true,
  "message": "Product deleted successfully."
}
```

### Upload d'une image produit

```bash
POST /api/v1/seller/products/{product}/images
Authorization: Bearer your-sanctum-token
Content-Type: multipart/form-data

file: [Binary image file]
```

**Réponse:**
```json
{
  "success": true,
  "message": "Product image uploaded successfully.",
  "data": {
    "image": {
      "id": 1,
      "path": "/storage/products/1/image.jpg",
      "position": 0
    },
    "image_count": 1,
    "max_images": 10
  }
}
```

### Réorganiser les images

```bash
PATCH /api/v1/seller/products/{product}/images/reorder
Authorization: Bearer your-sanctum-token
Content-Type: application/json

{
  "image_ids": [3, 1, 2]
}
```

**Réponse:**
```json
{
  "success": true,
  "message": "Product images reordered successfully.",
  "data": {
    "product": {
      "id": 1,
      "images": [
        {"id": 3, "position": 0},
        {"id": 1, "position": 1},
        {"id": 2, "position": 2}
      ]
    }
  }
}
```

### Supprimer une image

```bash
DELETE /api/v1/seller/products/{product}/images/{image}
Authorization: Bearer your-sanctum-token
```

**Réponse:**
```json
{
  "success": true,
  "message": "Product image deleted successfully."
}
```

### Définir l'image de couverture

```bash
POST /api/v1/seller/products/{product}/cover/{image}
Authorization: Bearer your-sanctum-token
```

**Réponse:**
```json
{
  "success": true,
  "message": "Product cover image set successfully.",
  "data": {
    "product_id": 1,
    "cover_image_id": 1,
    "cover_image_url": "/storage/products/1/image.jpg"
  }
}
```

### Supprimer l'image de couverture

```bash
DELETE /api/v1/seller/products/{product}/cover
Authorization: Bearer your-sanctum-token
```

**Réponse:**
```json
{
  "success": true,
  "message": "Product cover image removed successfully.",
  "data": {
    "product_id": 1,
    "cover_image_id": null
  }
}
```

## Commandes

### Lister les commandes

```bash
GET /api/v1/seller/orders
Authorization: Bearer your-sanctum-token
```

**Réponse:**
```json
{
  "success": true,
  "message": "Seller orders retrieved successfully.",
  "data": {
    "orders": [
      {
        "id": 1,
        "reference": "ORD-001",
        "status": "paid",
        "total_cents": 10000,
        "items": []
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 1
    }
  }
}
```

### Récupérer une commande

```bash
GET /api/v1/seller/orders/{order}
Authorization: Bearer your-sanctum-token
```

**Réponse:**
```json
{
  "success": true,
  "message": "Seller order retrieved successfully.",
  "data": {
    "order": {
      "id": 1,
      "reference": "ORD-001",
      "status": "paid",
      "total_cents": 10000,
      "items": []
    }
  }
}
```

### Marquer une commande comme prête

```bash
POST /api/v1/seller/orders/{order}/mark-ready
Authorization: Bearer your-sanctum-token
```

**Réponse:**
```json
{
  "success": true,
  "message": "Order marked as ready successfully.",
  "data": {
    "order": {
      "id": 1,
      "status": "ready"
    }
  }
}
```

### Demander l'annulation d'une commande

```bash
POST /api/v1/seller/orders/{order}/cancel-request
Authorization: Bearer your-sanctum-token
Content-Type: application/json

{
  "reason": "out_of_stock",
  "details": "Product is no longer available"
}
```

**Réponse:**
```json
{
  "success": true,
  "message": "Order cancellation requested successfully. Waiting for admin review.",
  "data": {
    "order": {
      "id": 1,
      "status": "cancel_requested",
      "cancel_reason": "out_of_stock"
    }
  }
}
```

### Générer un bon de préparation

```bash
GET /api/v1/seller/orders/{order}/packing-slip
Authorization: Bearer your-sanctum-token
```

**Réponse:**
```json
{
  "success": true,
  "message": "Packing slip generated successfully.",
  "data": {
    "packing_slip": {
      "order": {
        "id": 1,
        "reference": "ORD-001",
        "created_at": "2023-01-01T00:00:00.000000Z",
        "status": "paid"
      },
      "shop": {
        "name": "My Shop",
        "email": "seller@example.com",
        "phone": "+22912345678"
      },
      "customer": {
        "name": "John Doe",
        "phone": "+22998765432",
        "email": "customer@example.com"
      },
      "delivery_address": {
        "address_line_1": "123 Main St",
        "city": "Cotonou",
        "country": "Benin"
      },
      "items": [
        {
          "product_name": "Product 1",
          "variant": "N/A",
          "sku": "PROD-001",
          "quantity": 2,
          "unit_price": 5000,
          "total_price": 10000
        }
      ],
      "totals": {
        "subtotal": 10000,
        "delivery_fee": 0,
        "total": 10000,
        "currency": "XOF"
      }
    },
    "format": "json",
    "generated_at": "2023-01-01T00:00:00.000000Z"
  }
}
```

## Finance

### Récupérer le résumé financier

```bash
GET /api/v1/seller/finance/summary
Authorization: Bearer your-sanctum-token
```

**Réponse:**
```json
{
  "success": true,
  "message": "Seller finance summary retrieved successfully.",
  "data": {
    "summary": {
      "available_balance_cents": 100000,
      "pending_withdrawals_cents": 0,
      "currency": "XOF"
    }
  }
}
```

### Lister les retraits

```bash
GET /api/v1/seller/finance/withdrawals
Authorization: Bearer your-sanctum-token
```

**Réponse:**
```json
{
  "success": true,
  "message": "Seller withdrawals retrieved successfully.",
  "data": {
    "withdrawals": [
      {
        "id": 1,
        "amount_cents": 10000,
        "status": "paid",
        "created_at": "2023-01-01T00:00:00.000000Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 15,
      "total": 1
    }
  }
}
```

### Créer une demande de retrait

```bash
POST /api/v1/seller/finance/withdrawals
Authorization: Bearer your-sanctum-token
Idempotency-Key: unique-key-123
Content-Type: application/json

{
  "amount_cents": 10000
}
```

**Réponse:**
```json
{
  "success": true,
  "message": "Seller withdrawal request created successfully.",
  "data": {
    "withdrawal": {
      "id": 1,
      "amount_cents": 10000,
      "status": "pending",
      "currency": "XOF"
    }
  }
}
```

### Traiter un retrait

```bash
POST /api/v1/seller/finance/withdrawals/{withdrawal}/process
Authorization: Bearer your-sanctum-token
```

**Réponse:**
```json
{
  "success": true,
  "message": "Withdrawal processing job dispatched",
  "data": {
    "withdrawal_id": 1,
    "status": "queued"
  }
}
```

### Callback de retrait

```bash
POST /api/v1/seller/finance/withdrawals/{withdrawal}/callback
Content-Type: application/json

{
  "transaction_id": "txn_12345",
  "status": "completed"
}
```

**Réponse:**
```json
{
  "success": true,
  "message": "Callback processed successfully"
}
```

### Lister les transactions

```bash
GET /api/v1/seller/finance/transactions
Authorization: Bearer your-sanctum-token
```

**Paramètres optionnels:**
- `type`: Filtrer par type (sale,commission,withdrawal)
- `date_from`: Date de début
- `date_to`: Date de fin

**Réponse:**
```json
{
  "success": true,
  "message": "Seller transactions retrieved successfully.",
  "data": {
    "transactions": [
      {
        "id": "sale-1",
        "type": "sale",
        "type_label": "Sale",
        "reference": "ORD-001",
        "amount_cents": 10000,
        "amount": 100,
        "currency": "XOF",
        "status": "completed",
        "status_label": "Completed",
        "created_at": "2023-01-01T00:00:00.000000Z",
        "is_credit": true,
        "is_debit": false
      }
    ],
    "summary": {
      "total_transactions": 1,
      "total_sales": 10000,
      "total_commissions": 500,
      "total_withdrawals": 0,
      "net_balance": 9500
    }
  }
}
```

## Notifications

### Lister les notifications

```bash
GET /api/v1/seller/notifications
Authorization: Bearer your-sanctum-token
```

**Paramètres optionnels:**
- `type`: Filtrer par type
- `is_read`: Filtrer par statut de lecture

**Réponse:**
```json
{
  "success": true,
  "message": "Notifications retrieved successfully.",
  "data": {
    "notifications": [
      {
        "id": 1,
        "type": "order",
        "title": "New Order",
        "message": "You have a new order #ORD-001",
        "is_read": false,
        "created_at": "2023-01-01T00:00:00.000000Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 20,
      "total": 1
    },
    "summary": {
      "unread_count": 1
    }
  }
}
```

### Marquer une notification comme lue

```bash
POST /api/v1/seller/notifications/{notification}/read
Authorization: Bearer your-sanctum-token
```

**Réponse:**
```json
{
  "success": true,
  "message": "Notification marked as read.",
  "data": {
    "notification": {
      "id": 1,
      "is_read": true,
      "read_at": "2023-01-01T00:00:00.000000Z"
    }
  }
}
```

### Marquer toutes les notifications comme lues

```bash
POST /api/v1/seller/notifications/read-all
Authorization: Bearer your-sanctum-token
```

**Réponse:**
```json
{
  "success": true,
  "message": "All notifications marked as read.",
  "data": {
    "marked_count": 5
  }
}
```

### Supprimer une notification

```bash
DELETE /api/v1/seller/notifications/{notification}
Authorization: Bearer your-sanctum-token
```

**Réponse:**
```json
{
  "success": true,
  "message": "Notification deleted successfully."
}
```

## Chat

### Lister les conversations

```bash
GET /api/v1/seller/conversations
Authorization: Bearer your-sanctum-token
```

**Réponse:**
```json
{
  "success": true,
  "message": "Conversations retrieved successfully.",
  "data": {
    "conversations": [
      {
        "id": 1,
        "subject": "Order #ORD-001",
        "status": "open",
        "seller_unread_count": 0,
        "customer_unread_count": 1,
        "last_message": {
          "id": 1,
          "message": "Hello, I have a question",
          "created_at": "2023-01-01T00:00:00.000000Z"
        }
      }
    ]
  }
}
```

### Récupérer une conversation

```bash
GET /api/v1/seller/conversations/{conversation}
Authorization: Bearer your-sanctum-token
```

**Réponse:**
```json
{
  "success": true,
  "message": "Conversation retrieved successfully.",
  "data": {
    "conversation": {
      "id": 1,
      "subject": "Order #ORD-001",
      "status": "open",
      "messages": [
        {
          "id": 1,
          "sender_type": "customer",
          "message": "Hello, I have a question",
          "created_at": "2023-01-01T00:00:00.000000Z"
        }
      ]
    }
  }
}
```

### Envoyer un message

```bash
POST /api/v1/seller/conversations/{conversation}/messages
Authorization: Bearer your-sanctum-token
Content-Type: application/json

{
  "message": "Hello, how can I help you?"
}
```

**Réponse:**
```json
{
  "success": true,
  "message": "Message sent successfully.",
  "data": {
    "message": {
      "id": 2,
      "sender_type": "seller",
      "message": "Hello, how can I help you?",
      "created_at": "2023-01-01T00:00:00.000000Z"
    }
  }
}
```

## Codes d'erreur

| Code | Statut HTTP | Description |
|------|-------------|-------------|
| `VALIDATION_ERROR` | 422 | Erreur de validation des données |
| `UNAUTHENTICATED` | 401 | Non authentifié |
| `FORBIDDEN` | 403 | Accès interdit |
| `NOT_FOUND` | 404 | Ressource non trouvée |
| `CONFLICT` | 409 | Conflit (ex: ressource déjà existante) |
| `RATE_LIMIT_EXCEEDED` | 429 | Trop de requêtes |
| `SERVER_ERROR` | 500 | Erreur serveur |

## Rate Limiting

Les endpoints seller ont des limites de requêtes:

- Bootstrap: 60 requêtes/minute
- Lecture produits: 120 requêtes/minute
- Écriture produits: 30 requêtes/minute
- Commandes: 60 requêtes/minute
- Retraits: 10 requêtes/minute
- Uploads: 20 requêtes/minute

Les en-têtes de réponse incluent:
- `X-RateLimit-Limit`: Limite maximale
- `X-RateLimit-Remaining`: Requêtes restantes

## Bonnes pratiques

1. **Utilisez toujours l'en-tête Authorization** avec un token valide
2. **Gérez les erreurs** grace aux codes d'erreur standardisés
3. **Respectez les rate limits** pour éviter les blocages
4. **Utilisez les filtres et tris** pour optimiser les requêtes
5. **Validez les données** avant de les envoyer

## Support

Pour toute question ou problème, contactez le support technique à support@marketify.com.