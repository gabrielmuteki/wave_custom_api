<?php
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/services/WebhookService.php';

// Vérifier que la requête est en POST et contient un ID
$requestData = json_decode(file_get_contents('php://input'), true);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($requestData['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de notification manquant']);
    exit;
}

try {
    $db = (new Database())->connect();
    $webhookService = new WebhookService($db);

    // Récupérer les informations de la notification
    $stmt = $db->prepare("
        SELECT wn.*, cs.merchant_api_key
        FROM webhook_notifications wn
        JOIN checkout_sessions cs ON wn.session_id = cs.id
        WHERE wn.id = ?
    ");
    $stmt->execute([$requestData['id']]);
    $notification = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$notification) {
        http_response_code(404);
        echo json_encode(['error' => 'Notification non trouvée']);
        exit;
    }

    // Mettre à jour le statut et renvoyer le webhook
    $success = $webhookService->resendWebhook($notification);

    http_response_code($success ? 200 : 500);
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Webhook renvoyé avec succès' : 'Échec du renvoi du webhook'
    ]);

} catch (Exception $e) {
    error_log('Erreur lors du renvoi du webhook: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur interne du serveur']);
}
