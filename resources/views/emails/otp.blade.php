<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KORBO Verification Code</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background-color: #f6f8f7;
            margin: 0;
            padding: 20px;
            color: #14201b;
        }
        .container {
            max-width: 520px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #dfe8e3;
            padding: 36px 30px;
            text-align: center;
            box-shadow: 0 4px 18px rgba(11, 61, 46, 0.06);
        }
        .logo {
            font-size: 26px;
            font-weight: 800;
            color: #0b3d2e;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }
        h2 {
            font-size: 22px;
            color: #0b3d2e;
            margin-bottom: 10px;
        }
        p {
            font-size: 15px;
            color: #5b6b64;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .otp-box {
            display: inline-block;
            background: #e7f5ee;
            border: 2px dashed #12a47a;
            border-radius: 12px;
            padding: 16px 32px;
            font-size: 34px;
            font-weight: 800;
            color: #0b3d2e;
            letter-spacing: 8px;
            margin-bottom: 24px;
        }
        .footer {
            font-size: 13px;
            color: #8b9b94;
            margin-top: 30px;
            border-top: 1px solid #edf2ef;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">KORBO</div>
        <h2>Verification Code</h2>
        <p>Use the code below to log in or verify your account. This code is confidential and will expire in <strong>5 minutes</strong>.</p>
        
        <div class="otp-box">{{ $otp }}</div>
        
        <p>If you did not request this verification code, please ignore this email.</p>
        
        <div class="footer">
            &copy; {{ date('Y') }} KORBO — আপনার কাজ, আমরা করবো।<br>
            Verified Human Assistance & Administrative Marketplace
        </div>
    </div>
</body>
</html>
