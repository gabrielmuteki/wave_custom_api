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

// Récupérer les données JSON
$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;
$activate = $data['activate'] ?? null;

if ($id === null || $activate === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Paramètres manquants']);
    exit;
}

$db = (new Database())->connect();
$apiKeyModel = new ApiKey($db);

try {
    $stmt = $db->prepare("UPDATE api_keys SET is_active = ? WHERE id = ?");
    $result = $stmt->execute([intval($activate), $id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => "Statut de la clé API mis à jour avec succès"
        ]);
    } else {
        throw new Exception("Erreur lors de la mise à jour du statut de la clé API");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
