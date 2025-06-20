<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="css/authsheet.css"> 
    <style>
        .forgot-password-form {
            max-width: 400px;
            width: 100%;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .forgot-password-form h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .forgot-password-form input[type="email"] {
            width: calc(100% - 20px); /* Adjust for padding */
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .forgot-password-form button {
            width: 100%;
            padding: 10px;
            background-color: #000;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 16px;
        }
        .forgot-password-form button:hover {
            opacity: 0.9;
        }
        .forgot-password-message {
            margin-top: 15px;
            color: green;
        }
        .forgot-password-error {
            margin-top: 15px;
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left">
            <div class="top-bar">
                <div class="logo">
                    <img src="../../assets/staynest_logo.png" alt="StayNest Logo" />
                    <span>StayNest</span>
                </div>
                <div class="signin-link">
                    Remembered your password? <a href="signin.php">Sign In</a>
                </div>
            </div>

            <div class="forgot-password-form">
                <h2>Forgot Your Password?</h2>
                <p>Enter your email address below and we'll send you a link to reset your password.</p>
                <form id="forgotPasswordForm">
                    <input type="email" id="email" placeholder="Enter your email" required>
                    <button type="submit">Send Reset Link</button>
                </form>
                <div id="message" class="forgot-password-message"></div>
                <div id="error" class="forgot-password-error"></div>
            </div>
        </div>
        <div class="right"></div>
    </div>

    <script>
        document.getElementById('forgotPasswordForm').addEventListener('submit', function(event) {
            event.preventDefault(); 

            const email = document.getElementById('email').value;
            const messageDiv = document.getElementById('message');
            const errorDiv = document.getElementById('error');

            messageDiv.textContent = '';
            errorDiv.textContent = '';
            

            
            if (!email) {
                errorDiv.textContent = 'Please enter your email address.';
                return;
            }
            if (!/\S+@\S+\.\S+/.test(email)) { 
                errorDiv.textContent = 'Please enter a valid email address.';
                return;
            }

            
            console.log('Sending password reset request for:', email);

            
            setTimeout(() => {
                
                messageDiv.textContent = 'If an account with that email exists, a password reset link has been sent.';
               
                document.getElementById('email').value = ''; 
            }, 1000);
        });
    </script>
</body>
</html>