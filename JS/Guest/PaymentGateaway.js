document.addEventListener("DOMContentLoaded", function () {
   const payButton = document.getElementById("pay-button");
 
   payButton.addEventListener("click", function () {
     const name = document.getElementById("name").value.trim();
     const card = document.getElementById("card").value.trim();
     const cvv = document.getElementById("cvv").value.trim();
     const method = document.getElementById("payment-method").value;
 
     // Dummy validation
     if (name === "" || card.length !== 16 || cvv.length !== 3) {
       alert("Payment Failed: Please enter valid payment details.");
       window.location.href = "PaymentFailed.html"; // your failed page
     } else {
       alert("Payment Successful!");
       window.location.href = "PaymentSuccess.html"; // your success page
     }
   });
 });
 