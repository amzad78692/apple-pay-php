<script src="https://applepay.cdn-apple.com/jsapi/v1/apple-pay-sdk.js"></script>
<script src="https://unpkg.com/alpinejs" defer></script>

<style>
    apple-pay-button {
        --apple-pay-button-width: 140px;
        --apple-pay-button-height: 30px;
        --apple-pay-button-border-radius: 5px;
        --apple-pay-button-padding: 5px 0px;
        display: initial;
    }
</style>

<apple-pay-button buttonstyle="black" type="plain" locale="en" onclick="onApplePayButtonClicked(event)">
    Check out
</apple-pay-button>
<script src="<?= $_SERVER['HTTP_HOST'] ?>/apple-pay-php/apple-pay.js"></script>