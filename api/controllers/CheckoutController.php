<?php
class CheckoutController {
    private $db;
    private $checkoutSession;
    private $validationService;

    public function __construct($db) {
        $this->db = $db;
        $this->checkoutSession = new CheckoutSession($db);
        $this->validationService = new ValidationService();
    }    public function createSession() {
        // Vérifier l'authentification API
        $apiKey = $this->getApiKeyFromHeaders();
        if (!$apiKey) {
            Response::error(401, 'API key is required');
            return;
        }
        
        // Récupérer les informations du marchand à partir de la clé API
        $stmt = $this->db->prepare("SELECT merchant_id FROM api_keys WHERE api_key = ? AND is_active = 1");
        $stmt->execute([$apiKey]);
        $result = $stmt->fetch();
        
        if (!$result) {
            Response::error(401, 'Invalid or inactive API key');
            return;
        }
        
        $merchantId = $result['merchant_id'];

        // Récupérer et valider les données
        $data = json_decode(file_get_contents('php://input'), true);
        
        $validation = $this->validationService->validateCheckoutData($data);
        if (!$validation['valid']) {
            Response::error(400, 'Validation failed', $validation['errors']);
            return;
        }

        // Créer la session
        $sessionId = $this->generateSessionId();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $sessionData = [
            'id' => $sessionId,
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'XOF',            'client_reference' => $data['client_reference'] ?? null,
            'customer' => $data['customer'] ?? null,
            'description' => $data['description'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'aggregated_merchant_id' => $merchantId, // Utiliser l'ID du marchand de la clé API
            'success_url' => $data['success_url'],
            'cancel_url' => $data['cancel_url'],
            'payment_url' => $this->buildPaymentUrl($sessionId),
            'merchant_api_key' => $apiKey,
            'expires_at' => $expiresAt
        ];

        if ($this->checkoutSession->create($sessionData)) {            $response = [
                'id' => $sessionId,
                'amount' => $data['amount'],
                'currency' => $sessionData['currency'],
                'status' => 'pending',
                'payment_url' => $sessionData['payment_url'],
                'metadata' => $data['metadata'] ?? null,
                'customer' => $data['customer'] ?? null,
                'description' => $data['description'] ?? null,
                'expires_at' => date('c', strtotime($expiresAt)),
                'created_at' => date('c')
            ];

            // Include customer info if provided
            if (isset($data['customer'])) {
                $response['customer'] = $data['customer'];
            }

            Response::success($response);
        } else {
            Response::error(500, 'Failed to create checkout session');
        }
    }

    public function getSession($id) {
        $session = $this->checkoutSession->getById($id);
        
        if (!$session) {
            Response::error(404, 'Session not found');
            return;
        }        Response::success([
            'id' => $session['id'],
            'amount' => $session['amount'],
            'currency' => $session['currency'],
            'status' => $session['status'],
            'payment_url' => $session['payment_url'],
            'metadata' => json_decode($session['metadata'], true),
            'customer' => json_decode($session['customer_info'], true),
            'description' => $session['description'],
            'expires_at' => date('c', strtotime($session['expires_at'])),
            'created_at' => date('c', strtotime($session['created_at']))
        ]);
    }

    private function generateSessionId() {
        return 'cs_' . bin2hex(random_bytes(16));
    }    private function buildPaymentUrl($sessionId) {
        // return "http://localhost/epsiestartup/wave/pay/{$sessionId}";
        return "http://epsie-startup.com/wave/pay/{$sessionId}";
    }

    private function getApiKeyFromHeaders() {
        $headers = getallheaders();
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        if ($auth && preg_match('/^Bearer\s+(.+)$/', $auth, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function validateApiKey($apiKey) {
        $apiKeyModel = new ApiKey($this->db);
        return $apiKeyModel->validate($apiKey);
    }
}
?>
