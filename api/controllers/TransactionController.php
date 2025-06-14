<?php
class TransactionController {
    private $db;
    private $checkoutSession;

    public function __construct($db) {
        $this->db = $db;
        $this->checkoutSession = new CheckoutSession($db);
    }

    public function getAllTransactions() {
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

        // Requête pour obtenir toutes les transactions avec les informations des webhooks
        $query = "
            SELECT 
                cs.*,
                ak.merchant_name,
                wn.status as webhook_status,
                wn.payload as webhook_payload,
                wn.created_at as webhook_created_at
            FROM checkout_sessions cs
            LEFT JOIN api_keys ak ON cs.aggregated_merchant_id = ak.merchant_id
            LEFT JOIN webhook_notifications wn ON cs.id = wn.session_id
                AND wn.created_at = (
                    SELECT MAX(created_at)
                    FROM webhook_notifications
                    WHERE session_id = cs.id
                )
            WHERE cs.aggregated_merchant_id = ?
            ORDER BY cs.created_at DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$merchantId]);
        $transactions = $stmt->fetchAll();

        // Formater les résultats
        $formattedTransactions = array_map(function($transaction) {
            return [
                'id' => $transaction['id'],
                'amount' => $transaction['amount'],
                'currency' => $transaction['currency'],
                'status' => $transaction['status'],
                'merchant_name' => $transaction['merchant_name'],
                'client_reference' => $transaction['client_reference'],
                'description' => $transaction['description'],
                'metadata' => json_decode($transaction['metadata'], true),
                'customer' => json_decode($transaction['customer_info'], true),
                'webhook_status' => $transaction['webhook_status'],
                'webhook_details' => $transaction['webhook_payload'] ? json_decode($transaction['webhook_payload'], true) : null,
                'created_at' => date('c', strtotime($transaction['created_at'])),
                'completed_at' => $transaction['completed_at'] ? date('c', strtotime($transaction['completed_at'])) : null
            ];
        }, $transactions);

        Response::success([
            'total' => count($formattedTransactions),
            'transactions' => $formattedTransactions
        ]);
    }

    public function getTransactionsByDateRange() {
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

        // Récupérer et valider les dates
        $dateStart = $_GET['date_start'] ?? null;
        $dateEnd = $_GET['date_end'] ?? null;

        if (!$dateStart || !$dateEnd) {
            Response::error(400, 'Both date_start and date_end are required');
            return;
        }

        // Valider le format des dates
        if (!strtotime($dateStart) || !strtotime($dateEnd)) {
            Response::error(400, 'Invalid date format. Use YYYY-MM-DD');
            return;
        }

        $merchantId = $result['merchant_id'];

        // Requête pour obtenir les transactions dans l'intervalle de dates
        $query = "
            SELECT 
                cs.*,
                ak.merchant_name,
                wn.status as webhook_status,
                wn.payload as webhook_payload,
                wn.created_at as webhook_created_at
            FROM checkout_sessions cs
            LEFT JOIN api_keys ak ON cs.aggregated_merchant_id = ak.merchant_id
            LEFT JOIN webhook_notifications wn ON cs.id = wn.session_id
                AND wn.created_at = (
                    SELECT MAX(created_at)
                    FROM webhook_notifications
                    WHERE session_id = cs.id
                )
            WHERE cs.aggregated_merchant_id = ?
            AND DATE(cs.created_at) BETWEEN ? AND ?
            ORDER BY cs.created_at DESC";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$merchantId, $dateStart, $dateEnd]);
        $transactions = $stmt->fetchAll();

        // Formater les résultats
        $formattedTransactions = array_map(function($transaction) {
            return [
                'id' => $transaction['id'],
                'amount' => $transaction['amount'],
                'currency' => $transaction['currency'],
                'status' => $transaction['status'],
                'merchant_name' => $transaction['merchant_name'],
                'client_reference' => $transaction['client_reference'],
                'description' => $transaction['description'],
                'metadata' => json_decode($transaction['metadata'], true),
                'customer' => json_decode($transaction['customer_info'], true),
                'webhook_status' => $transaction['webhook_status'],
                'webhook_details' => $transaction['webhook_payload'] ? json_decode($transaction['webhook_payload'], true) : null,
                'created_at' => date('c', strtotime($transaction['created_at'])),
                'completed_at' => $transaction['completed_at'] ? date('c', strtotime($transaction['completed_at'])) : null
            ];
        }, $transactions);

        Response::success([
            'total' => count($formattedTransactions),
            'date_start' => $dateStart,
            'date_end' => $dateEnd,
            'transactions' => $formattedTransactions
        ]);
    }

    private function getApiKeyFromHeaders() {
        $headers = getallheaders();
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        if ($auth && preg_match('/^Bearer\s+(.+)$/', $auth, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
