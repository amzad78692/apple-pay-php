<?php
require_once __DIR__ . '/config/apple-pay.php';
require_once __DIR__ . '/config/knet.php';
class ApplePayService
{
    public function __construct()
    {
        // Constructor can be used for initialization if needed
    }

    public function authorizeMerchant($validationUrl = null)
    {
        // Use the URL Safari provides so the session matches the device's environment
        // (sandbox tester accounts use apple-pay-gateway-cert.apple.com). Only Apple hosts are allowed.
        $validationHost = $validationUrl ? parse_url($validationUrl, PHP_URL_HOST) : null;
        if (
            !$validationUrl
            || parse_url($validationUrl, PHP_URL_SCHEME) !== 'https'
            || !preg_match('/^apple-pay-gateway(-[a-z0-9-]+)?\.apple\.com$/', (string) $validationHost)
        ) {
            $validationUrl = VALIDATION_URL;
        }

        $postData = array(
            'merchantIdentifier' => MERCHANT_IDENTIFIER,
            'displayName' => DISPLAY_NAME,
            'domainName' => INITIATIVE_CONTEXT,
            'initiative' => INITIATIVE,
            'initiativeContext' => INITIATIVE_CONTEXT
        );

        $curlOptions = array(
            CURLOPT_URL => $validationUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSLCERT => MERCHANT_IDENTITY_CERTIFICATE,
            CURLOPT_SSLKEY => MERCHANT_IDENTITY_KEY,
            CURLOPT_SSLKEYPASSWD => MERCHANT_IDENTITY_KEY_PASSWORD,
            CURLOPT_SSL_VERIFYPEER => true
        );

        // Initialize cURL session and execute the request
        $curlConnection = curl_init();
        curl_setopt_array($curlConnection, $curlOptions);
        $response = curl_exec($curlConnection);
        $err = curl_error($curlConnection);
        $httpCode = curl_getinfo($curlConnection, CURLINFO_HTTP_CODE);
        curl_close($curlConnection);

        if ($err) {
            return ['status' => false, 'message' => $err];
        }

        $decoded = json_decode($response, true);
        if ($httpCode !== 200 || empty($decoded['merchantSessionIdentifier'])) {
            return ['status' => false, 'message' => $decoded['statusMessage'] ?? $response, 'http_code' => $httpCode];
        }
        return ['status' => true, 'response' => $decoded];
    }

    public function processPaymentResponse($applePayResponse, $amount, $reference, $paymentGateway = 'KNET')
    {
        if (is_string($applePayResponse)) {
            $applePayResponse = json_decode($applePayResponse, true);
        }
        switch (strtoupper($paymentGateway)) {
            case 'KNET':
                $id = KNET_ID;
                $password = KNET_PASSWORD;
                $action = '1';
                $currency = '414';
                $langid = 'EN';
                $amt = $amount;
                $trackid = $reference;
                $udf8 = $applePayResponse['token']['transactionIdentifier'] ?? '';
                $udf9 = $applePayResponse['token']['paymentData'] ?? '';
                $udf9 = json_encode($udf9);
                $udf10 = $applePayResponse['token']['paymentMethod'] ?? '';
                $udf10 = json_encode($udf10);
                $errorURL = KNET_ERROR_URL;
                $responseURL = KNET_RESPONSE_URL;

                // Build XML body
                $xml = <<<XML
                    <request>
                        <id>{$id}</id>
                        <password>{$password}</password>
                        <action>{$action}</action>
                        <currency>{$currency}</currency>
                        <langid>{$langid}</langid>
                        <amt>{$amt}</amt>
                        <trackid>{$trackid}</trackid>
                        <udf8>{$udf8}</udf8>
                        <udf9>{$udf9}</udf9>
                        <udf10>{$udf10}</udf10>
                        <errorURL>{$errorURL}</errorURL>
                        <responseURL>{$responseURL}</responseURL>
                    </request>
                XML;

                // Initialize cURL
                $ch = curl_init();

                // Set cURL options
                curl_setopt($ch, CURLOPT_URL, KNET_ENDPOINT);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Content-Type: application/xml",
                    "Accept: application/xml",
                    "Content-Length: " . strlen($xml),
                ]);

                // Execute request
                $response = curl_exec($ch);

                // Handle errors
                if (curl_errno($ch)) {
                    return ['status' => false, 'message' => curl_error($ch)];
                } else {
                    $wrappedXml = "<response>$response</response>";
                    $xmlObject = simplexml_load_string($wrappedXml);
                    $responseArray = json_decode(json_encode($xmlObject), true);

                    if (empty($responseArray['trackid'])) {
                        return ['status' => false, 'response' => $responseArray];
                    } else {
                        return ['status' => true, 'response' => $responseArray];
                    }
                }
            default:
                return ['status' => false, 'message' => 'Unsupported payment gateway'];
        }
    }
}