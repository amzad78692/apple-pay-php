# Apple Pay PHP Integration Package

A complete PHP package for integrating Apple Pay functionality with KNET payment gateway. This package provides server-side Apple Pay merchant validation, payment processing, and seamless integration with Kuwait's KNET payment system.

## Features

- 🍎 **Apple Pay Integration**: Full Apple Pay web integration with merchant validation
- 💳 **KNET Payment Gateway**: Direct integration with Kuwait's KNET payment system
- 🔒 **Secure Processing**: SSL certificate-based merchant authentication
- 📱 **Cross-Platform**: Works on Safari, iOS, and macOS devices with Apple Pay support
- 🚀 **Easy Setup**: Simple configuration and implementation

## Package Structure

```
apple-pay-php/
├── README.md                          # This documentation file
├── apple-pay.php                     # Apple Pay button HTML template
├── apple-pay.js                      # Client-side Apple Pay JavaScript logic
├── ajax.php                          # AJAX endpoint handler
├── ApplePayService.php               # Main Apple Pay service class
├── config/
│   ├── apple-pay.php                # Apple Pay configuration
│   └── knet.php                     # KNET payment gateway configuration
└── apple_pay_merchant_id_certificates/
    ├── merchant_id.pem              # Apple Pay merchant certificate
    └── merchant_id_key.pem          # Apple Pay merchant private key
```

## Requirements

- **PHP**: 7.4 or higher
- **cURL**: Enabled with SSL support
- **OpenSSL**: PHP OpenSSL extension
- **Apple Developer Account**: With Apple Pay merchant identity certificate
- **KNET Account**: Valid KNET merchant credentials
- **HTTPS**: SSL certificate for your domain (required by Apple Pay)

## Installation & Setup

### 1. Download and Extract

Extract the package to your web server directory:

```bash
/path/to/your/webroot/apple-pay-php/
```

### 2. Apple Pay Certificate Setup

#### A. Obtain Apple Pay Certificate

1. Log in to [Apple Developer Console](https://developer.apple.com/account/)
2. Navigate to **Certificates, Identifiers & Profiles**
3. Create a **Merchant ID** (if you don't have one)
4. Create an **Apple Pay Payment Processing Certificate**
5. Create an **Apple Pay Merchant Identity Certificate**

#### B. Generate Certificate Files

1. Generate a Certificate Signing Request (CSR) and private key:

   ```bash
   openssl req -new -newkey rsa:2048 -nodes -keyout merchant_id.key -out merchant_id.csr -subj "/CN=merchant.com.yourwebsite"
   ```

2. Submit the CSR to Apple Developer Console to get your merchant certificate

3. Download the merchant certificate from Apple (usually as `merchant_id.cer`)

4. Convert the Apple certificate to PEM format:

   ```bash
   openssl x509 -in merchant_id.cer -inform der -outform pem -out merchant_id.pem
   ```

5. Encrypt the private key with password and save as PEM:

   ```bash
   openssl rsa -in merchant_id.key -des3 -out merchant_id_key.pem -passout pass:22650262
   ```

6. Place the final files in `apple_pay_merchant_id_certificates/` directory:
   - `merchant_id.pem` - Your merchant certificate (from step 4)
   - `merchant_id_key.pem` - Your encrypted private key (from step 5)

### 3. Configuration

#### A. Apple Pay Configuration

Edit `config/apple-pay.php`:

```php
<?php
// Certificate paths (relative to package root)
define('MERCHANT_IDENTITY_CERTIFICATE', 'apple_pay_merchant_id_certificates/merchant_id.pem');
define('MERCHANT_IDENTITY_KEY', 'apple_pay_merchant_id_certificates/merchant_id_key.pem');

// Certificate password (if your private key is encrypted)
define('MERCHANT_IDENTITY_KEY_PASSWORD', 'your_certificate_password');

// Your business display name (shown to customers)
define('DISPLAY_NAME', 'Your Business Name');

// These are automatically configured:
// - MERCHANT_IDENTIFIER: Extracted from certificate
// - INITIATIVE_CONTEXT: Current domain
// - VALIDATION_URL: Apple's validation endpoint
```

#### B. KNET Configuration

Edit `config/knet.php`:

```php
<?php
// Your KNET merchant credentials
define('KNET_ID', 'your_knet_merchant_id');
define('KNET_PASSWORD', 'your_knet_password');

// KNET endpoints
define('KNET_ENDPOINT', 'https://www.kpaytest.com.kw/kpg/tranPipe.htm?param=tranInit&'); // Test
// define('KNET_ENDPOINT', 'https://www.kpay.com.kw/kpg/tranPipe.htm?param=tranInit&'); // Production

// Callback URLs (configure based on your application)
define('KNET_ERROR_URL', 'https://yourdomain.com/payment-error');
define('KNET_RESPONSE_URL', 'https://yourdomain.com/payment-success');
```

### 4. Domain Registration

Register your domain with Apple Pay:

1. In Apple Developer Console, go to your **Merchant ID**
2. Add your domain under **Merchant Domains**
3. Verify domain ownership by uploading the domain verification file

### 5. Web Server Setup

Ensure your web server:

- Has a valid SSL certificate (HTTPS required)
- Supports PHP with cURL and OpenSSL extensions
- Allows outbound HTTPS connections to Apple and KNET servers

## Usage

### Basic Implementation

#### 1. Create Payment Form

Create an HTML form with required fields:

```html
<!DOCTYPE html>
<html>
  <head>
    <title>Apple Pay Integration</title>
  </head>
  <body>
    <form id="payment-form">
      <!-- Required fields -->
      <input type="hidden" name="amount" value="10.500" />
      <!-- Amount in KWD -->
      <input type="hidden" name="reference" value="ORDER123" />
      <!-- Unique reference -->
      <input
        type="hidden"
        name="callback_url"
        value="https://yourdomain.com/payment-complete"
      />

      <!-- Optional fields -->
      <input type="hidden" name="payment_gateway" value="KNET" />
      <!-- Default: KNET -->

      <!-- Include the Apple Pay button -->
      <?php include 'apple-pay-php/apple-pay.php'; ?>
    </form>
  </body>
</html>
```

#### 2. Handle Payment Completion

Create a callback handler at your `callback_url`:

```php
<?php
// payment-complete.php
if ($_POST) {
    // KNET response data
    $trackid = $_POST['trackid'] ?? '';
    $paymentid = $_POST['paymentid'] ?? '';
    $result = $_POST['result'] ?? '';
    $auth = $_POST['auth'] ?? '';

    if ($result === 'CAPTURED') {
        // Payment successful
        echo "Payment successful! Transaction ID: " . $paymentid;
    } else {
        // Payment failed
        echo "Payment failed: " . $result;
    }
}
?>
```

### Advanced Implementation

#### Custom Amount and Reference

```html
<form id="dynamic-payment">
  <label>Amount (KWD):</label>
  <input type="number" name="amount" step="0.001" required />

  <label>Order Reference:</label>
  <input type="text" name="reference" required />

  <input
    type="hidden"
    name="callback_url"
    value="https://yourdomain.com/payment-complete"
  />

  <?php include 'apple-pay-php/apple-pay.php'; ?>
</form>
```

#### Multiple Payment Gateways

```html
<form id="payment-form">
  <input type="hidden" name="amount" value="25.750" />
  <input type="hidden" name="reference" value="ORDER456" />
  <input
    type="hidden"
    name="callback_url"
    value="https://yourdomain.com/payment-complete"
  />

  <select name="payment_gateway">
    <option value="KNET">KNET</option>
    <!-- Add more gateways here as implemented -->
  </select>

  <?php include 'apple-pay-php/apple-pay.php'; ?>
</form>
```

## API Reference

### ApplePayService Class

#### `authorizeMerchant()`

Validates merchant with Apple Pay servers.

**Returns:**

```php
[
    'status' => true|false,
    'response' => array, // Apple's merchant session
    'message' => string  // Error message if failed
]
```

#### `processPaymentResponse($applePayResponse, $amount, $reference, $paymentGateway)`

Processes Apple Pay payment through selected gateway.

**Parameters:**

- `$applePayResponse` (string|array): Apple Pay payment token
- `$amount` (string): Payment amount
- `$reference` (string): Unique transaction reference
- `$paymentGateway` (string): Payment gateway ('KNET')

**Returns:**

```php
[
    'status' => true|false,
    'response' => array,  // Gateway response data
    'message' => string   // Error message if failed
]
```

### AJAX Endpoints

#### Merchant Validation

```
POST /apple-pay-php/ajax.php?action=apple-pay-merchant-validation
```

#### Payment Processing

```
POST /apple-pay-php/ajax.php
Content-Type: application/x-www-form-urlencoded

action=apple-pay-response-processing
apple_pay_response=<json_token>
amount=<amount>
reference=<reference>
payment_gateway=KNET
```

## JavaScript Integration

### Required Form Fields

The JavaScript validates these required fields:

- `amount`: Payment amount (required)
- `callback_url`: Success callback URL (required)
- `reference`: Unique transaction reference (required)

### Apple Pay Availability

The package automatically checks:

- Apple Pay API availability
- Device/browser compatibility
- Payment method availability

### Events Handling

- **Merchant Validation**: Automatically handled
- **Payment Authorization**: Processes through selected gateway
- **Payment Completion**: Redirects to callback URL
- **Payment Cancellation**: Reloads current page

## Testing

### Test Environment Setup

1. Use KNET test endpoint in configuration
2. Use test merchant credentials
3. Test on Safari/iOS devices
4. Verify SSL certificate is valid

### Test Scenarios

- ✅ Successful payment flow
- ✅ Payment cancellation
- ✅ Network failure handling
- ✅ Invalid certificate handling
- ✅ Gateway timeout handling

## Security Considerations

1. **HTTPS Required**: Apple Pay only works over HTTPS
2. **Certificate Security**: Keep private keys secure and encrypted
3. **Domain Validation**: Only registered domains can process payments
4. **Amount Validation**: Server-side amount validation recommended
5. **Reference Uniqueness**: Use unique transaction references

## Troubleshooting

### Common Issues

#### "Apple Pay not available"

- Check browser/device compatibility (Safari, iOS, macOS)
- Verify HTTPS is enabled
- Ensure domain is registered with Apple

#### "Merchant validation failed"

- Verify certificate paths in config
- Check certificate expiry date
- Ensure outbound HTTPS connections are allowed
- Validate certificate password

#### "Payment processing failed"

- Check KNET credentials
- Verify KNET endpoint URL
- Ensure callback URLs are accessible
- Check server error logs

### Debug Mode

Enable error reporting for debugging:

```php
// Add to top of ajax.php for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Log Files

Monitor server error logs for detailed error messages:

- Apache: `/var/log/apache2/error.log`
- Nginx: `/var/log/nginx/error.log`
- PHP: Check `php.ini` for error log location

## Support

For technical support:

1. Check server error logs
2. Verify configuration files
3. Test with minimal implementation
4. Review Apple Developer documentation
5. Contact KNET support for gateway issues

## License

This package is provided as-is for educational and commercial use. Ensure compliance with Apple Pay terms of service and KNET merchant agreement.

## Changelog

### Version 1.0

- Initial release
- Apple Pay web integration
- KNET payment gateway support
- Merchant validation
- Payment processing
- Error handling
