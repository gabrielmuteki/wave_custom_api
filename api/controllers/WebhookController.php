<?php

class WebhookController {
    private $db;
    private $webhookService;

    public function __construct() {
        $this->db = (new Database())->connect();
        $this->webhookService = new WebhookService($this->db);
    }

    public function testWebhook() {
        try {
            // Récupérer le corps de la requête
            $requestData = json_decode(file_get_contents('php://input'), true);
            
            if (!$requestData) {
                return Response::json([
                    'success' => false,
                    'message' => 'Données de webhook invalides'
                ], 400);
            }            // Log de test
            error_log('Webhook test reçu: ' . json_encode($requestData));
            
            // Extraire et valider l'ID de session
            $sessionId = $requestData['data']['id'] ?? $requestData['session_id'] ?? null;
            if (!$sessionId) {
                return Response::json([
                    'success' => false,
                    'message' => 'ID de session manquant dans les données'
                ], 400);
            }
            
            // On initialise le statut à pending car c'est une nouvelle notification
            $this->webhookService->logWebhookNotification(
                $sessionId,
                'pending',
                $requestData
            );

            return Response::json([
                'success' => true,
                'message' => 'Webhook de test reçu avec succès',
                'data' => $requestData
            ], 200);

        } catch (Exception $e) {
            error_log('Erreur webhook test: ' . $e->getMessage());
            return Response::json([
                'success' => false,
                'message' => 'Erreur lors du traitement du webhook: ' . $e->getMessage()
            ], 500);
        }
    }
}
