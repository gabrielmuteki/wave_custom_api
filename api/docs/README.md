# WaveCustom API Documentation

## Introduction

WaveCustom est une API de simulation de paiement pour vos tests de développement. Cette API vous permet de :
- Créer des sessions de paiement
- Suivre l'état des paiements
- Recevoir des notifications webhooks
- Gérer les clés API et les statistiques de paiement (clé d'accès admin : ACCESS_WAVE_ADMIN_2025 | URL Admin : https://epsie-startup.com/wave/admin)
- Tester le fonctionnement de l'API : https://epsie-startup.com/test/

## NB
En production, utilisez ces URL pour les tests : 
 - URL Webhook (à la création d'une clé API) : https://epsie-startup.com/wave/webhook/test
 - Domaine de l'API (Test de l'API Wave) : https://epsie-startup.com
 - URL de succès (Test de l'API Wave) : https://epsie-startup.com/wave/success/
 - URL d'annulation (Test de l'API Wave) : https://epsie-startup.com/wave/error/


## Authentification

L'API utilise une authentification par clé API. Incluez votre clé API dans l'en-tête `Authorization` :

```
Authorization: Bearer YOUR_API_KEY
```

## Points de terminaison

### Sessions de paiement

#### Créer une session de paiement

```http
POST /wave/api/v1/checkout/sessions
```

**Corps de la requête :**
```json
{
    "amount": 1000,            // Montant en francs CFA (100 - 5,000,000)
    "currency": "XOF",         // Devise (XOF uniquement)
    "description": "string",   // Description du paiement (requis)
    "client_reference": "string", // Référence externe (optionnel)
    "customer": {              // Informations client (requis)
        "phone": "string",     // Numéro de téléphone au format international (ex: +2250141288879)
        "name": "string"       // Nom du client (optionnel)
    },
    "metadata": {              // Métadonnées personnalisées (optionnel)
        "key": "value"
    },
    "success_url": "string",   // URL de redirection après succès
    "cancel_url": "string"     // URL de redirection après annulation
}
```

**Réponse :**
```json
{
    "success": true,
    "data": {
        "id": "cs_123...",           // ID unique de la session
        "amount": 1000,              
        "currency": "XOF",
        "status": "pending",
        "payment_url": "string",     // URL de la page de paiement
        "description": "string",     // Description du paiement
        "metadata": {               // Si fourni dans la requête
            "key": "value"
        },
        "customer": {               // Informations client
            "phone": "+2250141288879",
            "name": "Jean Dupont"
        },
        "expires_at": "2025-06-10T15:55:11+00:00",    // Date d'expiration ISO 8601
        "created_at": "2025-06-10T14:55:11+00:00"     // Date de création ISO 8601
    },
    "timestamp": "2025-06-10T14:55:11+00:00"
}
```

#### Récupérer une session

```http
GET /wave/api/v1/checkout/sessions/{id}
```

**Paramètres URL :**
- `id` : L'ID de la session de paiement

**Réponse :**
```json
{
    "success": true,
    "data": {        "id": "cs_123...",
        "amount": 1000,
        "currency": "XOF",
        "status": "pending|completed|cancelled|expired",
        "payment_url": "string",
        "metadata": {
            "key": "value"
        },
    "customer": {
            "phone": "string",     // Ex: +2250141288879
            "name": "string"       // Optionnel
        },
        "expires_at": "2023-...",
        "created_at": "2023-..."
    },
    "timestamp": "2023-..."
}
```

### Webhooks

Les webhooks vous permettent de recevoir des notifications en temps réel lors des changements d'état des paiements.

#### Configurer un webhook de test

```http
POST /wave/webhook/test
```

**Corps de la requête :**
```json
{
    "url": "string",              // URL qui recevra les notifications
    "description": "string",      // Description (optionnel)
    "events": ["payment.completed", "payment.cancelled"]  // Événements à recevoir
}
```

**Réponse :**
```json
{
    "success": true,
    "data": {
        "id": "wh_123...",
        "url": "string",
        "description": "string",
        "events": ["payment.completed", "payment.cancelled"],
        "created_at": "2023-..."
    },
    "timestamp": "2023-..."
}
```

## Événements webhook

Les webhooks envoient des notifications POST à l'URL configurée avec le corps suivant :

```json
{
    "data": {
        "id": "cs_bef1793691bb2cfd4e652b067e74b84a",
        "amount": 80000,
        "status": "completed",
        "currency": "XOF",
        "completed_at": "2025-06-10T15:36:07+00:00",
        "client_reference": null
    },
    "event": "checkout.session.completed",
    "timestamp": 1749569767
}

```

### Types d'événements

Les statuts possibles pour une session sont :
- `pending` : En attente de paiement
- `completed` : Paiement réussi
- `cancelled` : Paiement annulé par le client
- `expired` : Session de paiement expirée
- `failed` : Échec du paiement

Les événements webhook correspondants sont :
- `checkout.session.completed` : Paiement réussi
- `checkout.session.cancelled` : Paiement annulé par le client
- `checkout.session.expired` : Session de paiement expirée
- `checkout.session.failed` : Échec du paiement

## Codes d'erreur

| Code HTTP | Description |
|-----------|-------------|
| 400 | Requête invalide (paramètres manquants ou invalides) |
| 401 | Non authentifié (clé API invalide ou manquante) |
| 404 | Ressource non trouvée |
| 500 | Erreur serveur interne |

## Limites et Validations

- Une session expire après 1 heure
- Montant minimum : 100 XOF
- Montant maximum : 5 000 000 XOF
- Format du numéro de téléphone : +225XXXXXXXXXX (13 chiffres)
- Les URLs de redirection (success_url et cancel_url) sont obligatoires
- La description est obligatoire
- Limites d'API : 100 requêtes par minute

## Guide d'utilisation rapide

Voici un exemple complet d'intégration de Wave dans votre application :

### 1. Création d'une session de paiement

```php
<?php
// Préparez les données de la session
$data = [
    "amount" => 5000,
    "currency" => "XOF",
    "description" => "Achat T-shirt EPSIE",
    "client_reference" => "CMD-123",  // Référence optionnelle pour votre système
    "customer" => [
        "phone" => "+2250141288879",  // Numéro de téléphone Wave du client (requis)
        "name" => "Jean Dupont"       // Nom du client (optionnel)
    ],
    "metadata" => [                   // Métadonnées personnalisées (optionnel)
        "product_id" => "TSH-001",
        "color" => "blue"
    ],
    "success_url" => "https://votre-site.com/success",  // URL de redirection après succès (requis)
    "cancel_url" => "https://votre-site.com/cancel"     // URL de redirection après annulation (requis)
];

// Envoyez la requête
$ch = curl_init('https://epsie-startup.com/wave/api/v1/checkout/sessions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer YOUR_API_KEY',
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$session = json_decode($response, true);

// Redirigez vers la page de paiement
if ($session['success']) {
    header('Location: ' . $session['data']['payment_url']);
    exit;
}
?>
```

### 2. Gestion des webhooks

```php
<?php
// webhook-handler.php

// Récupérez le payload
$payload = file_get_contents('php://input');
$event = json_decode($payload, true);

// Vérifiez le type d'événement
if ($event['type'] === 'payment.completed') {
    $session_id = $event['data']['session_id'];
    $amount = $event['data']['amount'];
    
    // Mettez à jour votre base de données
    $query = "UPDATE orders SET status = 'paid' WHERE session_id = ?";
    // Exécutez votre requête...
    
    // Envoyez un email de confirmation    $phone = $event['data']['customer']['phone'];
    // Vous pouvez utiliser un service SMS ici pour envoyer une confirmation
    sendSMS($phone, "Merci pour votre paiement de {$amount} XOF");
}

// Répondez avec un 200 OK
http_response_code(200);
echo json_encode(['status' => 'success']);
?>
```

### 3. Interface client type

```html
<form action="votre-script.php" method="POST">
    <div class="form-group">
        <label>Produit</label>
        <select name="product" required>
            <option value="tshirt">T-shirt EPSIE (5000 XOF)</option>
            <option value="cap">Casquette EPSIE (3000 XOF)</option>
        </select>
    </div>
      <div class="form-group">
        <label>Numéro de téléphone Wave</label>
        <input type="tel" name="phone" pattern="^\+[0-9]{12,}" placeholder="+2250141288879" required>
        <small class="form-text text-muted">Format: +225XXXXXXXXXX</small>
    </div>
    
    <div class="form-group">
        <label>Nom</label>
        <input type="text" name="name" required>
    </div>
    
    <button type="submit" class="btn btn-primary">
        Payer avec Wave
    </button>
</form>
```

### 4. Flux complet

1. Le client remplit le formulaire sur votre site
2. Votre serveur crée une session de paiement via l'API Wave
3. Le client est redirigé vers l'interface de paiement Wave
4. Le client effectue son paiement
5. Wave envoie un webhook à votre serveur pour confirmer le paiement
6. Votre serveur met à jour la commande et envoie une confirmation
7. Le client est redirigé vers votre page de succès

Cette intégration simple permet de commencer rapidement avec Wave tout en gardant la possibilité d'ajouter des fonctionnalités plus avancées selon vos besoins.

# URL de la page de test
https://epsie.com/test/

### Transactions

#### Récupérer toutes les transactions

```http
GET /wave/api/v1/transaction/all
```

**En-têtes de la requête :**
```
Authorization: Bearer YOUR_API_KEY
```

**Réponse :**
```json
{
    "success": true,
    "data": {
        "total": 2,
        "transactions": [
            {
                "id": "cs_123...",
                "amount": 1000,
                "currency": "XOF",
                "status": "completed",
                "description": "Achat T-shirt EPSIE",
                "client_reference": "CMD-123",
                "merchant_name": "EPSIE Store",
                "metadata": {
                    "product_id": "TSH-001",
                    "color": "blue"
                },
                "customer": {
                    "name": "KESSE REGIS",
                    "phone": "+2250789482390"
                },
                "webhook_status": "sent",
                "webhook_details": {
                    "event": "checkout.session.completed",
                    "data": {
                        // Détails du webhook
                    }
                },
                "created_at": "2025-06-10T14:55:11+00:00",
                "completed_at": "2025-06-10T15:00:11+00:00"
            }
        ]
    },
    "timestamp": "2025-06-10T14:55:11+00:00"
}
```

#### Récupérer les transactions par période

```http
GET /wave/api/v1/transaction/between
```

**En-têtes de la requête :**
```
Authorization: Bearer YOUR_API_KEY
```

**Paramètres de requête :**
- `date_start` : Date de début au format YYYY-MM-DD
- `date_end` : Date de fin au format YYYY-MM-DD

**Exemple de requête :**
```http
GET /wave/api/v1/transaction/between?date_start=2025-06-01&date_end=2025-06-10
```

**Réponse :**
```json
{
    "success": true,
    "data": {
        "total": 1,
        "date_start": "2025-06-01",
        "date_end": "2025-06-10",
        "transactions": [
            {
                "id": "cs_123...",
                "amount": 1000,
                "currency": "XOF",
                "status": "completed",
                "description": "Achat T-shirt EPSIE",
                "client_reference": "CMD-123",
                "merchant_name": "EPSIE Store",
                "metadata": {
                    "product_id": "TSH-001",
                    "color": "blue"
                },
                "customer": {
                    "name": "KESSE REGIS",
                    "phone": "+2250789482390"
                },
                "webhook_status": "sent",
                "webhook_details": {
                    "event": "checkout.session.completed",
                    "data": {
                        // Détails du webhook
                    }
                },
                "created_at": "2025-06-05T14:55:11+00:00",
                "completed_at": "2025-06-05T15:00:11+00:00"
            }
        ]
    },
    "timestamp": "2025-06-10T14:55:11+00:00"
}
```
