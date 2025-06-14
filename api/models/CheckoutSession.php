<?php
class CheckoutSession {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO checkout_sessions 
            (id, amount, currency, description, client_reference, customer_info,
             metadata, aggregated_merchant_id, success_url, cancel_url, 
             payment_url, merchant_api_key, expires_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['id'],
            $data['amount'],
            $data['currency'],
            $data['description'] ?? null,
            $data['client_reference'] ?? null,
            isset($data['customer']) ? json_encode($data['customer']) : null,
            isset($data['metadata']) ? json_encode($data['metadata']) : null,
            $data['aggregated_merchant_id'] ?? null,
            $data['success_url'],
            $data['cancel_url'],
            $data['payment_url'],
            $data['merchant_api_key'],
            $data['expires_at']
        ]);
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM checkout_sessions WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function updateStatus($id, $status) {
        $completedAt = ($status === 'completed') ? date('Y-m-d H:i:s') : null;
        $stmt = $this->db->prepare("
            UPDATE checkout_sessions 
            SET status = ?, completed_at = ? 
            WHERE id = ?
        ");
        return $stmt->execute([$status, $completedAt, $id]);
    }

    public function getByClientReference($reference, $apiKey) {
        $stmt = $this->db->prepare("
            SELECT * FROM checkout_sessions 
            WHERE client_reference = ? AND merchant_api_key = ?
        ");
        $stmt->execute([$reference, $apiKey]);
        return $stmt->fetchAll();
    }
}
?>
