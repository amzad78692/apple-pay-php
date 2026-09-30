<?php
require_once __DIR__ . '/ApplePayService.php';
$applePayService = new ApplePayService();

switch ($_REQUEST['action']) {
    case 'apple-pay-merchant-validation':
        $response = $applePayService->authorizeMerchant($_REQUEST['validation_url'] ?? null);
        echo json_encode($response);
        break;

    case 'apple-pay-response-processing':
        $response = $applePayService->processPaymentResponse($_REQUEST['apple_pay_response'], $_REQUEST['amount'], $_REQUEST['reference'], $_REQUEST['payment_gateway']);
        echo json_encode($response);
        break;

    default:
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'Invalid action']);
        break;
}