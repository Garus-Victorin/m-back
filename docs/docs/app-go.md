# App Go — Marketify Go (Livreur)

**Stack :** Angular 19 + Angular Material 3 + Capacitor 6 (APK Android)
**Cible :** Mobile uniquement — Android low-end, terrain, plein soleil
**Bundle max :** 800 KB

> Capacitor est indispensable ici pour : GPS background, caméra (photo preuve + QR), push notifications, haptics.

## Routes

```
/login                  # Auth
/availability           # Toggle En ligne / Hors ligne
/deliveries             # Courses disponibles
/delivery/:id           # Détail course active
/map/:id                # Carte itinéraire
/earnings               # Gains et historique
```

## Plugins Capacitor requis

```bash
npm install @capacitor/geolocation
npm install @capacitor/camera
npm install @capacitor/push-notifications
npm install @capacitor/haptics
npm install @capacitor/network
npm install @capacitor-community/background-runner  # GPS background
```

## Flux d'une course

```
[Livreur voit course disponible]
  → Accepter course
    → Statut: accepted
      → "Arriver chez vendeur"
        → Scan QR vendeur (Capacitor Camera + zxing)
          → Statut: picked
            → GPS partagé avec client
              → "Livraison en cours"
                → Statut: in_delivery
                  → Client fournit OTP 4 chiffres
                    → Saisir OTP dans app
                      → Statut: completed ✓
```

## Composants principaux

### Toggle disponibilité

- Gros switch ON/OFF (toute la page)
- ON → active GPS background + commence à recevoir courses
- OFF → stop GPS, plus de courses assignées

```typescript
onToggle(online: boolean) {
  this.driverService.setAvailability(online).subscribe();
  if (online) this.startLocationTracking();
  else this.stopLocationTracking();
}
```

### Liste courses `/deliveries`

Design UX : gros texte, fort contraste (lisible au soleil)

```
┌──────────────────────────────────────────┐
│  📦 Commande #1234                        │
│  Pickup: Marché Dantokpa, Cotonou         │
│  Livraison: Fidjrossè                     │
│  Distance: 3.2 km  |  Gain: 800 F        │
│  [ACCEPTER]   [REFUSER]                   │
└──────────────────────────────────────────┘
```

### Carte `/map/:id`

- `@angular/google-maps` → voir pickup + destination
- Marker livreur mis à jour toutes les 5s via API
- Bouton "Itinéraire" → ouvre Google Maps natif

### Workflow step-by-step `/delivery/:id`

- Un bouton par étape, plein écran, couleur distinctive
- Haptic feedback sur chaque action
- Photo preuve avant "Livré" (Capacitor Camera)

### Saisie OTP

- Champ 4 chiffres large, clavier numérique forcé
- Validation côté serveur → si correct → course complétée

## GPS Background

```typescript
// location.service.ts
async startTracking() {
  await BackgroundRunner.dispatchEvent({
    label: 'com.marketify.go.location',
    event: 'startTracking',
    details: { interval: 5000 }
  });
}
```

Position envoyée : `PUT /driver/location` toutes les 5s quand en ligne.

## Offline

Queue des mises à jour statut si réseau absent :
```typescript
// Si pas de réseau → stocker action dans IndexedDB
// Dès que réseau revient → vider la queue
Network.addListener('networkStatusChange', ({ connected }) => {
  if (connected) this.syncPendingActions();
});
```

## Design System

- Boutons : `mat-flat-button` taille large, padding 24px
- Texte principal : 18px minimum
- Couleurs : fort contraste WCAG AA
- Pas de `mat-table` (pas besoin), uniquement cards et listes simples

## Gains `/earnings`

- Montant du jour / semaine
- Liste courses avec montant par course
- Paiement hebdo automatique (MTN MoMo / Moov Money)
