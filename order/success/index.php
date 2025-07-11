<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        /* Reset default styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Body styling */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #f9f9fb 0%, #e8ecef 100%);
            font-family: 'Poppins', sans-serif;
        }

        /* Main container */
        .confirmation-container {
            background: #ffffff;
            padding: 40px 50px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
            max-width: 600px;
            width: 90%;
            text-align: center;
        }

        /* Header section */
        .header h1 {
            font-size: 32px;
            font-weight: 600;
            color: #1a2b49;
            margin-bottom: 8px;
        }

        .header .subtitle {
            font-size: 16px;
            color: #5a6a85;
        }

        /* Checkmark animation */
        .checkmark-circle {
            width: 80px;
            height: 80px;
            margin: 20px auto;
        }

        .checkmark__circle {
            stroke: #2e7d32;
            stroke-width: 2;
            stroke-miterlimit: 10;
            animation: fill 0.4s ease-in-out 0.4s forwards, scale 0.3s ease-in-out 0.9s both;
        }

        .checkmark__check {
            stroke: #2e7d32;
            stroke-width: 3;
            stroke-linecap: round;
            stroke-linejoin: round;
            animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
        }

        /* Details section */
        .details {
            margin-bottom: 30px;
        }

        .details p {
            font-size: 16px;
            color: #34415e;
            margin-bottom: 10px;
        }

        .email-note {
            font-size: 14px;
            color: #7a8799;
        }

        /* Button styling */
        .actions {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .btn {
            padding: 12px 25px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .primary-btn {
            background: linear-gradient(90deg, #2e7d32, #4caf50);
            color: #fff;
        }

        .primary-btn:hover {
            background: linear-gradient(90deg, #266b29, #3d8b40);
            transform: translateY(-2px);
        }

        .secondary-btn {
            background: #e8ecef;
            color: #34415e;
        }

        .secondary-btn:hover {
            background: #d7dde2;
            transform: translateY(-2px);
        }

        /* Footer */
        footer {
            margin-top: 25px;
            font-size: 13px;
            color: #7a8799;
        }

        footer a {
            color: #2e7d32;
            text-decoration: none;
        }

        footer a:hover {
            text-decoration: underline;
        }

        /* Animation keyframes */
        @keyframes stroke {
            100% {
                stroke-dashoffset: 0;
            }
        }

        @keyframes scale {

            0%,
            100% {
                transform: none;
            }

            50% {
                transform: scale3d(1.1, 1.1, 1);
            }
        }

        @keyframes fill {
            100% {
                box-shadow: inset 0px 0px 0px 30px #2e7d32;
            }
        }

        /* Responsive design */
        @media (max-width: 480px) {
            .confirmation-container {
                padding: 25px 30px;
            }

            .header h1 {
                font-size: 26px;
            }

            .details p {
                font-size: 14px;
            }

            .btn {
                padding: 10px 20px;
                font-size: 14px;
            }

            .actions {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>

<body>
    <div class="confirmation-container">
        <div class="header">
            <h1>Order Confirmed</h1>
            <p class="subtitle">Thank you for your purchase!</p>
        </div>
        <div class="checkmark-circle">
            <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none" />
                <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8" />
            </svg>
        </div>
        <div class="details">
            <p>We’ve sent your order confirmation and receipt to <?php echo htmlspecialchars($_SESSION['checkoutEmail']); ?> .</p>
            <p class="email-note">Please check your inbox (and spam/junk folder) for the details.</p>
        </div>
        <div class="actions">
            <a href="/" class="btn primary-btn">Return to Homepage</a>
            <a href="/order" class="btn secondary-btn">View Order</a>
        </div>
        <footer>
            <p>Need assistance? <a href="/support">Contact Support</a></p>
        </footer>
    </div>
</body>

</html>