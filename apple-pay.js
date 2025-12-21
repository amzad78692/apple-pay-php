const requiredFields = ["amount", "callback_url", "reference"];

function validateForm(event) {
  const form = event.target.closest("form");

  // Check if form exists
  if (!form) {
    return false;
  }
  const formData = new FormData(form);

  // Check required fields
  for (const field of requiredFields) {
    if (!formData.get(field)) {
      return false;
    }
  }
  return formData;
}

function applePayAvailability() {
  if (!window.ApplePaySession || !ApplePaySession.canMakePayments()) {
    return false;
  }
  return true;
}

function createFormUsingObject(dataObject, actionUrl) {
  const form = document.createElement("form");
  form.method = "POST";
  form.action = actionUrl;

  for (const key in dataObject) {
    if (dataObject.hasOwnProperty(key)) {
      const input = document.createElement("input");
      input.type = "hidden";
      input.name = key;
      input.value = dataObject[key];
      form.appendChild(input);
    }
  }
  return form;
}

async function onApplePayButtonClicked(event) {
  // Validate the form and get form data
  const formData = validateForm(event);
  if (!formData) {
    alert(
      "Please add all these required fields into the form: " +
        requiredFields.join(", ")
    );
    return;
  }

  // Check if Apple Pay is available immediately
  if (!applePayAvailability()) {
    alert("Apple Pay is not available on this device/browser");
    return;
  }

  let amount = formData.get("amount");

  // Create Apple Pay session immediately from user gesture
  const request = {
    countryCode: "KW",
    currencyCode: "KWD",
    supportedNetworks: ["visa", "masterCard", "amex", "discover"],
    merchantCapabilities: ["supports3DS"],
    total: {
      label: "Your card will be charged",
      amount: amount,
      type: "final",
    },
  };

  const session = new ApplePaySession(3, request);

  session.onvalidatemerchant = async (event) => {
    console.log("Validating merchant...", event);
    try {
      const response = await fetch(
        "/apple-pay-php/ajax.php?action=apple-pay-merchant-validation"
      );

      const merchantSession = await response.json();
      console.log("Merchant session received:", merchantSession.response);
      session.completeMerchantValidation(merchantSession.response);
    } catch (error) {
      console.error("Merchant validation failed:", error.message);
      session.abort();
    }
  };

  session.onpaymentauthorized = async (event) => {
    console.log("Payment authorized:", event);

    try {
      const paymentData = {
        action: "apple-pay-response-processing",
        amount: amount,
        payment_gateway: formData.get("payment_gateway") ?? "KNET",
        apple_pay_response: JSON.stringify(event.payment),
        reference: formData.get("reference"),
      };
      const params = new URLSearchParams();
      for (const [key, value] of Object.entries(paymentData)) {
        params.append(key, value);
      }

      const res = await fetch("/apple-pay-php/ajax.php", {
        method: "POST",
        body: params,
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
      });

      const responseData = await res.json();
      console.log("Server response:", responseData);

      if (responseData.status == true) {
        // Complete the payment with success
        session.completePayment(ApplePaySession.STATUS_SUCCESS);

        // Create a form element
        const form = createFormUsingObject(
          responseData.response,
          formData.get("callback_url")
        );
        document.body.appendChild(form);
        form.submit();
      } else {
        session.completePayment(ApplePaySession.STATUS_FAILURE);
        window.location.reload();
      }
    } catch (error) {
      console.error("Payment processing failed:", error);
      session.completePayment(ApplePaySession.STATUS_FAILURE);
      alert("Payment processing failed. Please try again.");
      window.location.reload();
    }
  };

  session.oncancel = (event) => {
    console.log("Payment cancelled by user");
    alert("Payment was cancelled.");
    window.location.reload();
  };

  // Begin the Apple Pay session immediately
  session.begin();
}
