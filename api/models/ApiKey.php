<?php
class ApiKey {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }    public function generate($merchantName, $webhookUrl = null) {
        $apiKey = 'wave_' . bin2hex(random_bytes(32));
        $merchantId = 'MID_' . substr(bin2hex(random_bytes(8)), 0, 16);
        
        $stmt = $this->db->prepare("
            INSERT INTO api_keys (api_key, merchant_name, webhook_url, merchant_id) 
            VALUES (?, ?, ?, ?)
        ");
          if ($stmt->execute([$apiKey, $merchantName, $webhookUrl, $merchantId])) {
            return $apiKey;
        }
        return false;
    }

    public function validate($apiKey) {
        if (!$apiKey || !str_starts_with($apiKey, 'wave_')) {
            return false;
        }

        $stmt = $this->db->prepare("
            SELECT id FROM api_keys 
            WHERE api_key = ? AND is_active = 1
        ");
        $stmt->execute([$apiKey]);
        
        $result = $stmt->fetch();
        
        if ($result) {
            // Mettre à jour last_used_at
            $this->updateLastUsed($apiKey);
            return true;
        }
        
        return false;
    }

    private function updateLastUsed($apiKey) {
        $stmt = $this->db->prepare("
            UPDATE api_keys SET last_used_at = NOW() WHERE api_key = ?
        ");
        $stmt->execute([$apiKey]);
    }
}
?>
