<?php
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/services/WebhookService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

try {
    $db = (new Database())->connect();
    $webhookService = new WebhookService($db);

    // Récupérer toutes les notifications échouées
    $stmt = $db->prepare("
        SELECT wn.*, cs.merchant_api_key
        FROM webhook_notifications wn
        JOIN checkout_sessions cs ON wn.session_id = cs.id
        WHERE wn.status = 'failed'
        ORDER BY wn.created_at DESC
    ");
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $success = 0;
    $failed = 0;

    // Renvoyer chaque notification
    foreach ($notifications as $notification) {
        if ($webhookService->resendWebhook($notification)) {
            $success++;
        } else {
            $failed++;
        }
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => sprintf(
            '%d webhook(s) renvoyé(s) avec succès, %d échec(s)',
            $success,
            $failed
        )
    ]);

} catch (Exception $e) {
    error_log('Erreur lors du renvoi des webhooks: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur interne du serveur']);
}
