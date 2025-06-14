<?php
class WebhookService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function sendPaymentNotification($session, $status) {
        // Récupérer l'URL webhook du marchand
        $webhookUrl = $this->getWebhookUrl($session['merchant_api_key']);
        
        if (!$webhookUrl) {
            return false;
        }

        $payload = [
            'event' => 'checkout.session.' . $status,
            'data' => [
                'id' => $session['id'],
                'status' => $status,
                'amount' => $session['amount'],
                'currency' => $session['currency'],
                'client_reference' => $session['client_reference'],
                'completed_at' => ($status === 'completed') ? date('c') : null
            ],
            'timestamp' => time()
        ];

        // Créer une nouvelle notification
        $notificationId = $this->storeNotification($session['id'], $webhookUrl, $payload);
        
        // Envoyer la notification
        $response = $this->sendWebhook($webhookUrl, $payload, $notificationId);
        
        return $response;
    }    public function logWebhookNotification($sessionId, $status, $data) {
        // Récupérer l'URL webhook et les infos du marchand
        $stmt = $this->db->prepare("
            SELECT ak.webhook_url, cs.merchant_api_key
            FROM checkout_sessions cs
            JOIN api_keys ak ON cs.merchant_api_key = ak.api_key
            WHERE cs.id = ?
        ");
        $stmt->execute([$sessionId]);
        $result = $stmt->fetch();

        if (!$result || !$result['webhook_url']) {
            error_log("Webhook URL not found for session: " . $sessionId);
            return false;
        }

        // Vérifier si une notification non envoyée existe déjà
        $stmt = $this->db->prepare("
            SELECT id, attempts 
            FROM webhook_notifications 
            WHERE session_id = ? AND status != 'sent'
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$sessionId]);
        $existing = $stmt->fetch();        if ($existing) {
            // Mettre à jour la notification existante avec le nouveau compteur de tentatives
            $stmt = $this->db->prepare("
                UPDATE webhook_notifications 
                SET attempts = ?,
                    last_attempt_at = NOW(),
                    status = ?,
                    payload = ?
                WHERE id = ?
            ");
            return $stmt->execute([
                ($existing['attempts']),
                $status,
                json_encode($data),
                $existing['id']
            ]);
        }        // Créer une nouvelle notification
        $stmt = $this->db->prepare("
            INSERT INTO webhook_notifications 
            (session_id, webhook_url, status, payload, attempts, created_at, last_attempt_at)
            VALUES (?, ?, ?, ?, 0, NOW(), NULL)
        ");
        if (!$stmt->execute([$sessionId, $result['webhook_url'], $status, json_encode($data)])) {
            error_log("Failed to create notification for session: " . $sessionId);
            return false;
        }
        
        return $this->db->lastInsertId();
    }

    private function sendWebhook($url, $payload, $notificationId) {
        if (!$notificationId) {
            return false;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Wave-Signature: ' . $this->generateSignature($payload)
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $success = ($httpCode >= 200 && $httpCode < 300);
        $this->updateNotificationStatus($notificationId, $success ? 'sent' : 'failed');
        
        return $success;
    }

    private function generateSignature($payload) {
        return hash_hmac('sha256', json_encode($payload), 'webhook_secret_key');
    }

    private function getWebhookUrl($apiKey) {
        $stmt = $this->db->prepare("SELECT webhook_url FROM api_keys WHERE api_key = ?");
        $stmt->execute([$apiKey]);
        $result = $stmt->fetch();
        return $result ? $result['webhook_url'] : null;
    }

    private function storeNotification($sessionId, $webhookUrl, $payload) {
        $stmt = $this->db->prepare("
            INSERT INTO webhook_notifications 
            (session_id, webhook_url, payload, status, attempts, created_at, last_attempt_at) 
            VALUES (?, ?, ?, 'pending', 0, NOW(), NULL)
        ");
        $stmt->execute([$sessionId, $webhookUrl, json_encode($payload)]);
        return $this->db->lastInsertId();
    }    private function updateNotificationStatus($notificationId, $status) {
        $stmt = $this->db->prepare("
            UPDATE webhook_notifications 
            SET status = ?, 
                last_attempt_at = NOW(), 
                attempts = attempts + 1 
            WHERE id = ?
        ");
        return $stmt->execute([$status, $notificationId]);
    }

    public function resendWebhook($notification) {
        if (!$notification || !isset($notification['webhook_url'])) {
            error_log("Invalid notification data for resend");
            return false;
        }

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $notification['webhook_url'],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $notification['payload'],
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'X-Wave-Signature: ' . $this->generateSignature(json_decode($notification['payload'], true))
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => false
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $success = ($httpCode >= 200 && $httpCode < 300);
            
            // Mettre à jour le statut de la notification
            $this->updateNotificationStatus(
                $notification['id'],
                $success ? 'sent' : 'failed'
            );

            return $success;

        } catch (Exception $e) {
            error_log("Error resending webhook: " . $e->getMessage());
            return false;
        }
    }
}
?>
