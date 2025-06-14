<?php
class Response {
    public static function success($data = null, $code = 200) {
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'timestamp' => date('c')
        ], JSON_PRETTY_PRINT);
        exit;
    }

    public static function error($code, $message, $details = null) {
        http_response_code($code);
        $response = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message
            ],
            'timestamp' => date('c')
        ];
        
        if ($details) {
            $response['error']['details'] = $details;
        }
          echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    public static function json($data, $code = 200) {
        http_response_code($code);
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
}
?>
