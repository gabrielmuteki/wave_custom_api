<?php
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/models/ApiKey.php';
require_once 'auth.php';

// Vérifier l'authentification
requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$db = (new Database())->connect();
$apiKeyModel = new ApiKey($db);

$merchantName = $_POST['merchant_name'] ?? '';
$webhookUrl = $_POST['webhook_url'] ?? '';

if (empty($merchantName)) {
    http_response_code(400);
    echo json_encode(['error' => 'Le nom du marchand est requis']);
    exit;
}

try {
    $newApiKey = $apiKeyModel->generate($merchantName, $webhookUrl);
    if ($newApiKey) {
        echo json_encode([
            'success' => true,
            'message' => "Nouvelle clé API créée avec succès",
            'key' => $newApiKey
        ]);
    } else {
        throw new Exception("Erreur lors de la création de la clé API");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
