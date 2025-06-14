<?php
class ValidationService {
      public function validateCheckoutData($data) {
        $errors = [];
        
        // Vérifier les champs obligatoires
        $required = ['amount', 'success_url', 'cancel_url', 'customer'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[] = "Field '{$field}' is required";
            }
        }
        
        // Validate metadata if present
        if (isset($data['metadata'])) {
            if (!is_array($data['metadata'])) {
                $errors[] = "metadata must be a JSON object";
            } else {
                $encodedMetadata = json_encode($data['metadata']);
                if (strlen($encodedMetadata) > 4096) {
                    $errors[] = "metadata too large (max 4KB)";
                }
            }
        }
        
        // Validate description if present
        if (isset($data['description'])) {
            if (strlen($data['description']) > 1000) {
                $errors[] = "description too long (max 1000 characters)";
            }
        }

        // Valider les informations client
        if (isset($data['customer'])) {
            if (!isset($data['customer']['phone']) || empty($data['customer']['phone'])) {
                $errors[] = "Customer phone number is required";
            } else {
                // Valider le format du numéro de téléphone (+225XXXXXXXXXX)
                if (!preg_match('/^\+225[0-9]{10}$/', $data['customer']['phone'])) {
                    $errors[] = "Invalid phone number format. Must be in format +225XXXXXXXXXX";
                }
            }

            if (isset($data['customer']['name'])) {
                if (strlen($data['customer']['name']) > 100) {
                    $errors[] = "Customer name too long (max 100 characters)";
                }
            }
        }
        
        // Valider le montant
        if (isset($data['amount'])) {
            if (!is_int($data['amount']) || $data['amount'] <= 0) {
                $errors[] = "Amount must be a positive integer in XOF (no decimals)";
            }
            if ($data['amount'] > 5000000) { // 5M XOF max
                $errors[] = "Amount exceeds maximum limit (5,000,000 XOF)";
            }
        }
        
        // Valider la devise
        if (isset($data['currency'])) {
            if ($data['currency'] !== 'XOF') {
                $errors[] = "Only XOF (Franc CFA BCEAO) currency is supported";
            }
        }
          // Valider les URLs
        if (isset($data['success_url']) && !filter_var($data['success_url'], FILTER_VALIDATE_URL)) {
            $errors[] = "Invalid success_url";
        }
        if (isset($data['cancel_url']) && !filter_var($data['cancel_url'], FILTER_VALIDATE_URL)) {
            $errors[] = "Invalid cancel_url";
        }
        
        // Valider client_reference
        if (isset($data['client_reference']) && strlen($data['client_reference']) > 255) {
            $errors[] = "client_reference too long (max 255 characters)";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
?>
