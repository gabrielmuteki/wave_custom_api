<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/utils/Router.php';
require_once __DIR__ . '/utils/Response.php';
require_once __DIR__ . '/controllers/CheckoutController.php';
require_once __DIR__ . '/controllers/PaymentController.php';
require_once __DIR__ . '/controllers/WebhookController.php';
require_once __DIR__ . '/controllers/TransactionController.php';
require_once __DIR__ . '/models/CheckoutSession.php';
require_once __DIR__ . '/models/ApiKey.php';
require_once __DIR__ . '/services/ValidationService.php';
require_once __DIR__ . '/services/WebhookService.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Debug information
error_log("=== Wave API Request Debug ===");
error_log("Server Name: " . $_SERVER['SERVER_NAME']);
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("Script Name: " . $_SERVER['SCRIPT_NAME']);
error_log("PATH_INFO: " . (isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : 'not set'));
error_log("PHP_SELF: " . $_SERVER['PHP_SELF']);
error_log("Headers: " . json_encode(getallheaders()));
error_log("Raw input: " . file_get_contents('php://input'));

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$router = new Router();

// Routes API
$router->addRoute('POST', '/wave/api/v1/checkout/sessions', 'CheckoutController', 'createSession');
$router->addRoute('GET', '/wave/api/v1/checkout/sessions/{id}', 'CheckoutController', 'getSession');

// Routes interface de paiement
$router->addRoute('GET', '/wave/pay/{sessionId}', 'PaymentController', 'showPaymentPage');
$router->addRoute('POST', '/wave/pay/process', 'PaymentController', 'processPayment');

// Routes webhook
$router->addRoute('POST', '/wave/webhook/test', 'WebhookController', 'testWebhook');

// Routes transactions
$router->addRoute('GET', '/wave/api/v1/transaction/all', 'TransactionController', 'getAllTransactions');
$router->addRoute('GET', '/wave/api/v1/transaction/between', 'TransactionController', 'getTransactionsByDateRange');

$router->route();
?>
