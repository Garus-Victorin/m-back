# 🔧 ROADMAP BACKEND — Marketify

> **Stack :** Laravel 11, MySQL, Redis, CinetPay API
> **Cible :** API REST pour les apps Admin, Seller, Shopper, Go
> **État :** ❌ Backend non opérationnel — Base de données vide

---

## 📊 État Actuel

| Composant | Statut | Notes |
|---|---|---|
| Laravel 11 installé | ✅ OK | `composer.json` mis à jour |
| Base de données | ❌ Manquante | Tables non créées |
| Authentification | ❌ Manquante | Pas de UserController |
| Routes API | ❌ Manquantes | `routes/api.php` vide |
| Middleware Auth | ❌ Manquant | Pas de `auth:api` |
| CORS | ❌ Manquant | Pas de `config/cors.php` |
| Rate Limiting | ❌ Manquant | Pas de `config/rate-limiting.php` |
| Tests | ❌ Manquants | Pas de tests unitaires |
| Documentation API | ❌ Manquante | Pas de `docs/api.md` |

---

## 🔧 Plan d'Implémentation Complet

### Étape 1 : Configuration de Base (Jour 1)
- [] Configurer `.env` avec DB_MYSQL et REDIS
- [] Configurer `config/database.php` pour MySQL
- [] Configurer `config/auth.php` pour API tokens
- [] Configurer `config/cors.php` pour les apps frontend
- [] Configurer `config/rate-limiting.php` pour les APIs publiques

### Étape 2 : Migrations & Modèles (Jour 1-2)
- [] Créer migrations pour toutes les tables (voir `docs/database.md`)
- [] Créer modèles Eloquent pour chaque table
- [] Configurer relations entre modèles
- [] Configurer casts et mutators

### Étape 3 : Authentification (Jour 2)
- [] Créer `app/Http/Controllers/Api/AuthController.php`
  - [] `login()` → retourne token API
  - [] `register()` → crée User + Shop si seller
  - [] `logout()` → invalide token
  - [] `me()` → retourne User authentifié
- [] Créer `app/Http/Middleware/Authenticate.php` pour API
- [] Configurer `routes/api.php` pour les routes auth

### Étape 4 : Routes API (Jour 2-3)
- [] Créer routes pour chaque Resource (Users, Products, Orders, etc.)
- [] Appliquer middleware `auth:api` aux routes protégées
- [] Appliquer rate limiting aux routes publiques

### Étape 5 : Controllers & Services (Jour 3-4)
- [] Créer controllers pour chaque Resource
- [] Créer services pour la logique métier complexe
- [] Implémenter les validations Request
- [] Configurer les réponses JSON standardisées

### Étape 6 : Tests (Jour 4)
- [] Créer tests unitaires pour les services
- [] Créer tests fonctionnels pour les routes
- [] Configurer `phpunit.xml`

### Étape 7 : Documentation (Jour 4)
- [] Documenter les endpoints dans `docs/api.md`
- [] Créer Postman collection

---

## 🔴 LACUNES DU BACKEND

| # | Lacune | Impact | Priorité |
|---|---|---|---|
| B1 | **Base de données non configurée** — Pas de tables | API inutilisable | 🔴 Critique |
| B2 | **Pas d'authentification** — Pas de UserController | API non sécurisée | 🔴 Critique |
| B3 | **Pas de routes API** — `routes/api.php` vide | API non accessible | 🔴 Critique |
| B4 | **Pas de middleware Auth** — Pas de `auth:api` | API non protégée | 🔴 Critique |
| B5 | **Pas de CORS** — Pas de `config/cors.php` | Frontend bloqué | 🔴 Critique |
| B6 | **Pas de rate limiting** — Pas de `config/rate-limiting.php` | API vulnérable aux attaques | 🟡 Haute |
| B7 | **Pas de tests** — Pas de tests unitaires | Code non fiable | 🟡 Haute |
| B8 | **Pas de documentation API** — Pas de `docs/api.md` | Développement bloqué | 🟠 Moyenne |

---

## ✅ Critères de Validation

- [] `php artisan migrate:fresh --seed` fonctionne
- [] `http://localhost:8000/api/login` retourne un token
- [] `http://localhost:8000/api/me` retourne les infos User
- [] Les routes protégées nécessitent un token valide
- [] Les requêtes CORS sont autorisées
- [] Les requêtes sont rate limited
- [] Les tests passent avec `php artisan test`
- [] La documentation API est complète

---

## 📅 Planning Estimé

| Étape | Durée | Livrable |
|---|---|---|
| Configuration de Base | 1 jour | `.env`, `config/*` configurés |
| Migrations & Modèles | 2 jours | Tables créées, modèles configurés |
| Authentification | 1 jour | `AuthController`, routes auth |
| Routes API | 2 jours | Routes configurées, middleware appliqué |
| Controllers & Services | 2 jours | API fonctionnelle |
| Tests | 1 jour | Tests configurés et passant |
| Documentation | 1 jour | `docs/api.md` et Postman collection |

---

## 📌 Notes

- **Priorité :** Commencer par la configuration de base et les migrations
- **Risques :** Problèmes de configuration MySQL/Redis
- **Dépendances :** Frontend et apps mobiles dépendent de ce backend
