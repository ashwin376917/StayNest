document.addEventListener("DOMContentLoaded", function () {
    const payButton = document.getElementById("pay-button");

    if (payButton) {
        payButton.addEventListener("click", function (e) {
            const card = document.querySelector('input[name="card_number"]').value.trim();
            const cvv = document.querySelector('input[name="cvv"]').value.trim();
            const method = document.querySelector('select[name="payment_method"]').value;

            // Initialize an array to store all validation errors
            const errors = [];

            // 1. Check if a payment method is selected
            if (method === "") {
                errors.push("Please select a payment method.");
            }

            // 2. Check card number length (16 characters).
            //    This client-side check ONLY cares about length.
            //    It deliberately DOES NOT check for digits here, to allow
            //    non-digit characters (like alphabets) to pass to the server
            //    for the demonstration of server-side validation failure.
            if (card.length !== 16) {
                errors.push("Card number must be exactly 16 characters long.");
            }

            // 3. Check CVV length (3 characters).
            //    This client-side check ONLY cares about length.
            //    It deliberately DOES NOT check for digits here, to allow
            //    non-digit characters (like alphabets) to pass to the server
            //    for the demonstration of server-side validation failure.
            if (cvv.length !== 3) {
                errors.push("CVV must be exactly 3 characters long.");
            }

            // If any client-side errors (length or method selection) were found,
            // prevent form submission and show them.
            if (errors.length > 0) {
                e.preventDefault(); // Stop form submission
                alert("Payment Failed:\n" + errors.join("\n")); // Join all errors with newlines
            }

            // If no client-side errors were found, the form will submit.
            // The PHP script will then use `ctype_digit()` to validate if
            // the card number and CVV consist purely of digits. If they contain
            // alphabets, PHP will catch it and redirect with the 'invalid_card_details' error.
        });
    }

    // Handle error messages that are passed via URL parameters from the PHP script
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');

    if (error) {
        let errorMessage = "An unknown error occurred.";
        switch (error) {
            case 'invalid_card_details':
                errorMessage = "Please enter valid 16-digit card number and 3-digit CVV.";
                break;
            case 'transaction_failed':
                errorMessage = "Payment processing failed. Please try again or contact support.";
                break;
                // Add other specific error cases from your PHP here if needed
        }
        alert("Payment Error: " + errorMessage);

        // Clean the URL to prevent the alert from showing again on page refresh
        urlParams.delete('error');
        // history.replaceState changes the URL in the browser's address bar without reloading the page
        history.replaceState(null, '', '?' + urlParams.toString());
    }
});