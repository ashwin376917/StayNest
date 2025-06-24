// paymentValidation.js
document.addEventListener("DOMContentLoaded", function () {
  // Prevent back button navigation
  history.pushState(null, "", location.href);
  window.addEventListener("popstate", function () {
    history.pushState(null, "", location.href);
    alert("Back navigation is disabled on this page.");
  });

  // Payment validation
  const payButton = document.getElementById("pay-button");

  if (payButton) {
    payButton.addEventListener("click", function (e) {
      e.preventDefault();

      const card = document.querySelector('input[name="card_number"]').value.trim();
      const cvv = document.querySelector('input[name="cvv"]').value.trim();
      const method = document.querySelector('select[name="payment_method"]').value;

      if (card.length !== 16 || cvv.length !== 3 || method === "") {
        alert("Payment Failed: Please enter valid payment details.");
        window.location.href = "PaymentFailed.html";
      } else {
        alert("Payment Successful!");
        window.location.href = "PaymentSuccess.html";
      }
    });
  }
});
