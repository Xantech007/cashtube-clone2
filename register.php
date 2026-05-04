<?php
// register.php
session_start();
require_once 'database/conn.php';
require_once 'inc/countries.php';

// Function to detect country from IP
function detectCountryFromIp() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $url = "https://ipapi.co/{$ip}/country_name/";
    $response = @file_get_contents($url);
    if ($response === false) {
        file_put_contents('debug.log', "Failed to fetch country from ipapi.co for IP: {$ip}\n", FILE_APPEND);
        return 'Nigeria';
    }
    $country = trim($response);
    return in_array($country, $GLOBALS['countries']) ? $country : 'Nigeria';
}

$response = ['success' => false, 'error' => ''];

// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registerData'])) {
    $data = json_decode($_POST['registerData'], true);

    if (!empty($data['name']) && !empty($data['email']) && !empty($data['gender']) && !empty($data['country']) && isset($data['password'])) {

        $name     = trim($data['name']);
        $email    = trim($data['email']);
        $gender   = $data['gender'];
        $country  = trim($data['country']);
        $password = $data['password']; // allow 1+ char

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['error'] = "Invalid email format.";
        }
        elseif (!in_array($country, $countries)) {
            $response['error'] = "Invalid country selected.";
        }
        // CHANGED: Minimum 1 character (was 8)
        elseif (strlen($password) < 1) {
            $response['error'] = "Password must be at least 1 character long.";
        }
        else {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetchColumn() > 0) {
                    $response['error'] = "Email already registered.";
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = $pdo->prepare("INSERT INTO users (name, email, gender, passcode, country) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $email, $gender, $hashedPassword, $country]);

                    $userId = $pdo->lastInsertId();

                    $_SESSION['user_id']   = $userId;
                    $_SESSION['email']     = $email;
                    $_SESSION['passcode']  = $hashedPassword;

                    $response['success'] = true;
                }
            } catch (PDOException $e) {
                $response['error'] = "Database error occurred.";
                file_put_contents('debug.log', 'DB Error: '.$e->getMessage()."\n", FILE_APPEND);
            }
        }
    } else {
        $response['error'] = "Please fill all required fields.";
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$detected_country = detectCountryFromIp();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Register for Task Tube to start earning money by watching video ads. Create your account today!">
    <meta name="keywords" content="Task Tube, register, earn money, watch ads, passive income">
    <meta name="author" content="Task Tube">
    <title>Task Tube - Register</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }
        body {
            background: #f5f7fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: #333;
            padding-top: 80px;
            padding-bottom: 100px;
        }
        .hero-section {
            background: linear-gradient(135deg, #6e44ff, #b5179e);
            color: #fff;
            text-align: center;
            padding: 100px 20px;
            position: relative;
            overflow: hidden;
            z-index: 10;
        }
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://source.unsplash.com/random/1920x1080/?technology') no-repeat center center/cover;
            opacity: 0.1;
            z-index: 0;
        }
        .hero-section h1 {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        .hero-section p {
            font-size: 18px;
            line-height: 1.6;
            max-width: 600px;
            margin: 0 auto 30px;
            position: relative;
            z-index: 1;
        }
        .index-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .section-title {
            font-size: 36px;
            font-weight: 600;
            color: #333;
            text-align: center;
            margin-bottom: 40px;
        }
        .register-content {
            max-width: 500px;
            margin: 0 auto;
            background: #fff;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .register-content h2 {
            font-size: 28px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        .register-content p {
            font-size: 16px;
            color: #666;
            margin-bottom: 20px;
        }
        .register-content p span {
            color: #6e44ff;
            font-weight: 500;
        }
        .input-field, .country-select {
            width: 100%;
            height: 50px;
            font-size: 16px;
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            margin-bottom: 20px;
            outline: none;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .country-select {
            appearance: none;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12"><path fill="%23333" d="M6 8.5L0 2.5h12z"/></svg>') no-repeat right 15px center;
            background-size: 12px;
        }
        .input-field:focus, .country-select:focus {
            border-color: #6e44ff;
            box-shadow: 0 0 5px rgba(110, 68, 255, 0.3);
        }
        .gender-options {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        .gender-options label {
            display: flex;
            align-items: center;
            font-size: 16px;
            color: #333;
            cursor: pointer;
            gap: 5px;
        }
        .gender-options input[type="radio"] {
            width: 18px;
            height: 18px;
            accent-color: #6e44ff;
        }
        .btn {
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 500;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-block;
        }
        .submit-btn {
            background: #6e44ff;
            color: #fff;
            border: none;
            border-radius: 25px;
            padding: 15px;
            font-size: 18px;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s ease, transform 0.2s ease;
        }
        .submit-btn:hover {
            background: #5a00b5;
            transform: translateY(-2px);
        }
        .login-link {
            font-size: 14px;
            color: #666;
            margin-top: 20px;
        }
        .login-link a {
            color: #6e44ff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .login-link a:hover {
            color: #ff69b4;
            text-decoration: underline;
        }
        .cta-banner {
            background: linear-gradient(135deg, #6e44ff, #b5179e);
            color: #fff;
            text-align: center;
            padding: 60px 20px;
            border-radius: 15px;
            margin: 40px 20px;
        }
        .cta-banner h2 {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .cta-banner .btn {
            background-color: #fff;
            color: #6e44ff;
            padding: 15px 40px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 50px;
            transition: background-color 0.3s ease;
        }
        .cta-banner .btn:hover {
            background-color: #f0f0f0;
        }
        .notice {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            padding: 30px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            display: none;
            z-index: 1002;
        }
        .notice h2 {
            font-size: 24px;
            color: #6e44ff;
            margin-bottom: 15px;
        }
        .notice p {
            font-size: 16px;
            color: #666;
            margin-bottom: 20px;
            text-align: center;
        }
        .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #999;
            transition: color 0.3s ease;
        }
        .close-btn:hover {
            color: #333;
        }
        .notice .btn {
            background-color: #6e44ff;
            color: #fff;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 500;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .notice .btn:hover {
            background-color: #5a00b5;
        }
        @media (max-width: 1024px) {
            .hero-section h1 { font-size: 36px; }
            .hero-section p { font-size: 16px; }
            .section-title { font-size: 30px; }
            .register-content { padding: 20px; }
        }
        @media (max-width: 768px) {
            body { padding-top: 70px; padding-bottom: 80px; }
            .hero-section { padding: 80px 20px; }
            .hero-section h1 { font-size: 32px; }
            .hero-section p { font-size: 15px; }
            .section-title { font-size: 28px; }
            .register-content { padding: 20px; margin: 0 20px; }
            .input-field, .country-select { height: 45px; font-size: 15px; }
            .submit-btn { padding: 12px; font-size: 16px; }
            .cta-banner h2 { font-size: 28px; }
        }
        @media (max-width: 480px) {
            body { padding-top: 60px; padding-bottom: 60px; }
            .hero-section { padding: 60px 15px; }
            .hero-section h1 { font-size: 28px; }
            .hero-section p { font-size: 14px; }
            .section-title { font-size: 24px; }
            .register-content { padding: 15px; margin: 0 15px; }
            .gender-options { flex-direction: column; gap: 10px; }
            .gender-options label { font-size: 14px; }
            .cta-banner { padding: 40px 15px; }
            .cta-banner h2 { font-size: 24px; }
            .cta-banner .btn { padding: 12px 30px; font-size: 16px; }
        }
    </style>
</head>
<body>
    <?php include 'inc/header.php'; ?>
    <?php include 'inc/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <h1>Join Task Tube</h1>
        <p>Create your account to start earning money by watching video ads on our crypto-powered platform.</p>
    </section>

    <!-- Register Form -->
    <div class="index-container">
        <h2 class="section-title">Create Your Account</h2>
        <div class="register-content">
            <h2>Register for <span>Task Tube</span></h2>
            <p>Fill in your details to get started</p>
            <form id="register-form" method="POST">
                <input type="text" id="name" name="name" class="input-field" placeholder="Full Name" required>
                <input type="email" id="email" name="email" class="input-field" placeholder="Email Address" required>
                <input type="password" id="password" name="password" class="input-field" placeholder="Password (create a password you can remember)" required>
                <select id="country" name="country" class="country-select" required>
                    <option value="" disabled>Select your country</option>
                    <?php foreach ($countries as $country): ?>
                        <option value="<?php echo htmlspecialchars($country); ?>" <?php echo $country === $detected_country ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($country); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="gender-options">
                    <label><input type="radio" name="gender" value="male" required> Male</label>
                    <label><input type="radio" name="gender" value="female"> Female</label>
                    <label><input type="radio" name="gender" value="other"> Other</label>
                </div>
                <button type="submit" class="submit-btn btn">Submit</button>
            </form>
            <p class="login-link">Already have an account? <a href="signin.php">Sign In</a></p>
        </div>
    </div>

    <!-- CTA Banner -->
    <section class="cta-banner">
        <h2>Start Earning with Task Tube</h2>
        <a href="register.php" class="btn">Join Now</a>
    </section>

    <!-- Notice Popup -->
    <div class="notice" id="notice">
        <span class="close-btn" onclick="closeNotice()">x</span>
        <h2>Join Task Tube Today</h2>
        <p>Start earning money by watching video ads with our easy-to-use platform. Register now and turn your screen time into income!</p>
        <a href="register.php" class="btn">Get Started</a>
    </div>

    <?php include 'inc/footer.php'; ?>

    <!-- LiveChat Script -->
    <script>
        window.__lc = window.__lc || {};
        window.__lc.license = 15808029;
        (function(n,t,c){function i(n){return e._h?e._h.apply(null,n):e._q.push(n)}var e={_q:[],_h:null,_v:"2.0",on:function(){i(["on",c.call(arguments)]))},once:function(){i(["once",c.call(arguments)])},off:function(){i(["off",c.call(arguments)])},get:function(){if(!e._h)throw new Error("[LiveChatWidget] You can't use getters before load.");return i(["get",c.call(arguments)])},call:function(){i(["call",c.call(arguments)])},init:function(){var n=t.createElement("script");n.async=!0;n.type="text/javascript";n.src="https://cdn.livechatinc.com/tracking.js",t.head.appendChild(n)}};!n.__lc.asyncInit&&e.init(),n.LiveChatWidget=n.LiveChatWidget||e}(window,document,[].slice))
    </script>

    <script>
        // Set Active Navbar Link
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname.split('/').pop();
            const links = document.querySelectorAll('.ham-menu ul li a');
            links.forEach(link => {
                if (link.getAttribute('href') === currentPath || (currentPath === '' && link.getAttribute('href') === 'index.php')) {
                    link.parentElement.classList.add('active');
                }
            });
        });

        // Notice Popup
        function isNoticeShown() { return localStorage.getItem('noticeShownRegister'); }
        function setNoticeShown() { localStorage.setItem('noticeShownRegister', true); }
        function showNotice() {
            if (!isNoticeShown()) {
                setTimeout(() => {
                    document.getElementById('notice').style.display = 'block';
                    setNoticeShown();
                }, 2000);
            }
        }
        function closeNotice() {
            document.getElementById('notice').style.display = 'none';
            setNoticeShown();
        }
        window.addEventListener('load', showNotice);

        // Form Submission - UPDATED: Allow 1+ character password
        document.getElementById('register-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const name     = document.getElementById('name').value.trim();
            const email    = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            const country  = document.getElementById('country').value;
            const gender   = document.querySelector('input[name="gender"]:checked')?.value;

            if (!name || !email || !password || !country || !gender) {
                Swal.fire('Error', 'Please fill all fields and select gender.', 'error');
                return;
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                Swal.fire('Error', 'Please enter a valid email address.', 'error');
                return;
            }

            // CHANGED: Only require 1 character
            if (password.length < 1) {
                Swal.fire('Error', 'Password must be at least 1 character long.', 'error');
                return;
            }

            const data = { name, email, password, country, gender };

            $.ajax({
                url: './register.php',
                type: 'POST',
                data: { registerData: JSON.stringify(data) },
                contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Success!', 'Your account has been created.', 'success')
                            .then(() => window.location.href = './users/home.php');
                    } else {
                        Swal.fire('Error', response.error || 'Registration failed.', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Connection error. Please try again.', 'error');
                }
            });
        });

        // Prevent right-click only on non-link elements
        document.addEventListener('contextmenu', e => {
            if (!e.target.closest('a')) e.preventDefault();
        });
    </script>
</body>
</html>
