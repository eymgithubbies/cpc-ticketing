<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] === 'staff') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: employee/dashboard.php');
    }
    exit();
}

$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT HELPDESK - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            height: 100%;
        }

        body {
            background: #f5f7fa;
            height: 100%;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
        }

        #accesspanel {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 400px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 0;
            overflow: hidden;
        }

        #accesspanel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #1a237e 0%, #3949ab 100%);
        }

        #litheader {
            text-align: center;
            padding: 40px 0 20px;
            font-size: 24px;
            color: #1a237e;
            font-weight: 700;
            margin: 0;
        }

        .inset {
            padding: 0 30px 20px;
        }

        .inset p {
            margin-bottom: 15px;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 15px;
            margin: 0;
            background: white;
            border: 2px solid #e0e0e0;
            color: #333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 15px;
            border-radius: 10px;
            outline: none;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus, input[type="password"]:focus {
            border-color: #3949ab;
            box-shadow: 0 0 0 3px rgba(57, 73, 171, 0.1);
        }

        input[type="text"]::placeholder,
        input[type="password"]::placeholder {
            color: #666;
        }

        .checkbox-section {
            text-align: center;
            margin: 20px 0;
            color: #666;
            font-size: 12px;
        }

        .checkboxouter {
            display: inline-block;
            position: relative;
            width: 16px;
            height: 16px;
            margin-right: 8px;
            vertical-align: middle;
        }

        .checkboxouter input[type="checkbox"] {
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
            position: absolute;
            z-index: 2;
        }

        .checkbox {
            position: absolute;
            top: 0;
            left: 0;
            width: 16px;
            height: 16px;
            border: 1px solid #444;
            border-radius: 3px;
            background: rgba(0, 20, 0, 0.8);
            transition: all 0.3s ease;
        }

        .checkboxouter input[type="checkbox"]:checked + .checkbox {
            background: rgba(0, 255, 65, 0.2);
            border-color: #00ff41;
        }

        .checkboxouter input[type="checkbox"]:checked + .checkbox::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #00ff41;
            font-size: 11px;
        }

        .p-container {
            padding: 0 30px 30px;
            margin: 0;
        }

        input[type="submit"] {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1a237e 0%, #3949ab 100%);
            border: none;
            color: white;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        input[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 35, 126, 0.3);
        }

        input[type="submit"].denied {
            background: #c62828;
            animation: shake 0.5s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-5px); }
            40% { transform: translateX(5px); }
            60% { transform: translateX(-5px); }
            80% { transform: translateX(5px); }
        }

        .alert-error {
            background: #ffebee;
            border: 1px solid #ffcdd2;
            color: #c62828;
            padding: 12px 15px;
            margin: 0 30px 20px;
            border-radius: 10px;
            font-size: 14px;
        }

        .links-section {
            display: flex;
            justify-content: space-between;
            padding: 0 30px 20px;
            font-size: 11px;
        }

        .links-section a {
            color: #444;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .links-section a:hover {
            color: #1a237e;
        }

        .logo-container {
            text-align: center;
            margin-top: 40px;
            margin-bottom: 1px;
        }

        .logo-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid #1a237e;
            overflow: hidden;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(26, 35, 126, 0.2);
        }

        .logo-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .system-info {
            position: fixed;
            bottom: 20px;
            left: 20px;
            color: #666;
            font-size: 11px;
        }


        @media (max-width: 480px) {
            #accesspanel {
                width: 90%;
                max-width: 360px;
            }

            .logo-circle {
                width: 60px;
                height: 60px;
            }

            #litheader {
                font-size: 20px;
            }

            .inset, .p-container, .links-section {
                padding: 0 20px;
            }

            input[type="text"], input[type="password"] {
                padding: 12px;
                font-size: 16px;
            }

            input[type="submit"] {
                padding: 12px;
                font-size: 15px;
            }

            .system-info {
                display: none;
            }
        }
    </style>
</head>
<body>
    <form id="accesspanel" action="auth/login.php" method="post">
        <div class="logo-container">
            <div class="logo-circle">
                <img src="Logi%20image/cpc.png" alt="CPC Logo">
            </div>
        </div>
        <h1 id="litheader">CPCMC-TICKETING</h1>
        
        <?php if ($error): ?>
            <div class="alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="inset">
            <p>
                <input type="text" name="username" id="email" placeholder="Username" required autocomplete="off">
            </p>
            <p>
                <input type="password" name="password" id="password" placeholder="Password" required>
            </p>
            <div class="checkbox-section">
                <div class="checkboxouter">
                    <input type="checkbox" name="rememberme" id="remember" value="Remember">
                    <label class="checkbox"></label>
                </div>
                <label for="remember">Remember me for 14 days</label>
            </div>
        </div>
        <p class="p-container">
            <input type="submit" name="Login" id="go" value="Login">
        </p>
        <div class="links-section">
            <a href="register.php">[ REGISTER ]</a>
            <a href="#" onclick="alert('Contact IT Department'); return false;">[ FORGOT ]</a>
        </div>
    </form>

    <div class="system-info">
        IT HELPDESK v2.4
    </div>

</body>
</html>
