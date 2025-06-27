document.addEventListener("DOMContentLoaded", function () {
  const payButton = document.getElementById("pay-button");

  if (payButton) {
    payButton.addEventListener("click", function (e) {
      const card = document.querySelector('input[name="card_number"]').value.trim();
      const cvv = document.querySelector('input[name="cvv"]').value.trim();
      const method = document.querySelector('select[name="payment_method"]').value;

      if (card.length !== 16 || cvv.length !== 3 || method === "") {
        e.preventDefault();  // Block form submit
        alert("Payment Failed: Please enter valid payment details.");
      }
      // If valid, form submits to PHP, DB gets filled
    });
  }
});
