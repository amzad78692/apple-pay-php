<?php
define('MERCHANT_IDENTITY_CERTIFICATE', 'apple_pay_merchant_id_certificates/merchant_id.pem');
define('MERCHANT_IDENTITY_KEY', 'apple_pay_merchant_id_certificates/merchant_id_key.pem');

if (!file_exists(MERCHANT_IDENTITY_CERTIFICATE)) {
    throw new Exception('Merchant identity certificate file not found.');
} elseif (!file_exists(MERCHANT_IDENTITY_KEY)) {
    throw new Exception('Merchant identity key file not found.');
}

define('MERCHANT_IDENTITY_KEY_PASSWORD', 22650262);
define('DISPLAY_NAME', 'website name');
define('MERCHANT_IDENTIFIER', openssl_x509_parse(file_get_contents(MERCHANT_IDENTITY_CERTIFICATE))['subject']['UID']);
define('INITIATIVE', 'web');
define('INITIATIVE_CONTEXT', $_SERVER['HTTP_HOST']);
define('VALIDATION_URL', 'https://apple-pay-gateway.apple.com/paymentservices/paymentSession');