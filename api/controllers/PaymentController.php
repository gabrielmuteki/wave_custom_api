<?php
class PaymentController {
    private $db;
    private $checkoutSession;
    private $webhookService;

    public function __construct($db) {
        $this->db = $db;
        $this->checkoutSession = new CheckoutSession($db);
        $this->webhookService = new WebhookService($db);
    }

    public function showPaymentPage($sessionId) {
        $session = $this->checkoutSession->getById($sessionId);
        
        if (!$session) {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['error' => 'Session not found']);
            exit;
            return;
        }

        if ($session['status'] !== 'pending') {
            $this->redirectToError('Session is not pending');
            return;
        }

        if (strtotime($session['expires_at']) < time()) {
            $this->checkoutSession->updateStatus($sessionId, 'expired');
            $this->redirectToError('Session expired');
            return;
        }

        // Afficher l'interface de paiement
        $this->renderPaymentInterface($session);
    }

    public function processPayment() {
        $sessionId = $_POST['session_id'] ?? null;
        $action = $_POST['action'] ?? null; // 'pay' ou 'cancel'
        
        if (!$sessionId || !$action) {
            Response::error(400, 'Missing required parameters');
            return;
        }

        $session = $this->checkoutSession->getById($sessionId);
        if (!$session || $session['status'] !== 'pending') {
            Response::error(400, 'Invalid session');
            return;
        }

        if ($action === 'pay') {
            $this->processSuccessfulPayment($session);
        } else if ($action === 'cancel') {
            $this->processCancelledPayment($session);
        } else {
            Response::error(400, 'Invalid action');
        }
    }

    private function processSuccessfulPayment($session) {
        // Simuler un délai de traitement
        sleep(2);
        
        // Mettre à jour le statut
        $this->checkoutSession->updateStatus($session['id'], 'completed');
        
        // Envoyer le webhook
        $this->webhookService->sendPaymentNotification($session, 'completed');
        
        // Logger l'action
        $this->logPaymentAction($session['id'], 'payment_completed');
        
        // Rediriger vers la page de succès
        header("Location: " . $session['success_url']);
        exit;
    }

    private function processCancelledPayment($session) {
        $this->checkoutSession->updateStatus($session['id'], 'cancelled');
        $this->webhookService->sendPaymentNotification($session, 'cancelled');
        $this->logPaymentAction($session['id'], 'payment_cancelled');
        
        header("Location: " . $session['cancel_url']);
        exit;
    }

    private function renderPaymentInterface($session) {
        header('Content-Type: text/html; charset=utf-8');
        include __DIR__ . '/../../pay/payment-interface.php';
    }

    private function redirectToError($message) {
        header("Location: /wave/error?message=" . urlencode($message));
        exit;
    }

    private function logPaymentAction($sessionId, $action, $details = null) {
        $stmt = $this->db->prepare("
            INSERT INTO payment_logs (session_id, action, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $sessionId,
            $action,
            $details ? json_encode($details) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
}
?>
