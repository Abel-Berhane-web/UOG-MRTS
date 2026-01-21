<?php
session_start();
require __DIR__ . '/dashboard/email_functions.php';

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "test_uog";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = trim($_POST['username_or_email']);

    // Fetch user
    $stmt = $conn->prepare("SELECT id, fullname, email, is_approved FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $input, $input);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ((int)$user['is_approved'] !== 1) {
            $message = "Your account is not yet approved.";
        } else {
            // Generate temporary password
            $temp_password = bin2hex(random_bytes(4)); // 8 chars
            $hashed = password_hash($temp_password, PASSWORD_DEFAULT);

            // Update DB
            $update = $conn->prepare("UPDATE users SET password = ?, temp_password_flag = 0 WHERE id = ?");
            $update->bind_param("si", $hashed, $user['id']);
            $update->execute();

            // Send email
            if (notifyUserTemporaryPassword($user['email'], $user['fullname'], $temp_password)) {
                $message = "Temporary password sent to your registered email. Please log in and change it immediately.";
            } else {
                $message = "Failed to send email. Contact admin.";
            }
        }
    } else {
        $message = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - UoG MRTS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="forgot_password.css">
    <style>
        
    </style>
</head>
<body>
    <!-- NAVIGATION -->
    <nav class="nav">
        <div class="nav-inner">
            <div class="brand">
                <span style="font-size: 30px; color: #2CB955;"><a href="home.html">UoG-MRTS</a></span>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <div class="forgot-container" style="backdrop-filter: blur(10px); margin-bottom: 40px;">
        <h2>Forgot Password</h2>
        <p class="form-description">Enter your username or email to reset your password</p>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, 'sent') !== false ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" autocomplete="off">
            <div class="form-group">
                <label for="username_or_email">Username or Email</label>
                <input type="text" id="username_or_email" name="username_or_email" placeholder="Enter your username or email" required>
            </div>
            
            <input type="submit" value="Send Temporary Password">
        </form>
        
        <div class="back-link">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>

     <!-- FOOTER -->
    <footer>
        <div class="footer-inner">
            <div>
                <div class="brand" style="margin-bottom: 10px">
                    <div class="brand-badge"><svg viewBox="0 0 24 24"><path d="M12 2l3.2 6.5 7.1 1-5.1 5 1.2 7.1L12 18.8 5.6 21.6 6.8 14.5 1.7 9.5l7.1-1z"/></svg></div>
                    <span style="font-size: 30px; color: #2CB955; ">UoG-MRTS</span>
                </div>
            </div>
            <div>
                <strong>Product</strong><br>
                <div style="margin-top: 20px;"></div>
                <div style="margin-top: 10px;" class="muted"><a href="home.html#features">Features</a></div>
                <div style="margin-top: 10px;" class="muted"><a href="home.html#workflow">Workflow</a></div>
                <div style="margin-top: 10px;" class="muted"><a href="home.html#roles">Roles</a></div>
            </div>
            <div>
                <strong>Support</strong><br>
                <div style="margin-top: 20px;"></div>
                <div style="margin-top: 10px;" class="muted"><a href="home.html#faq">FAQ</a></div>
                <div style="margin-top: 10px;" class="muted"><a href="home.html#get-started">Get started</a></div>
            </div>
            <div class="credits">© <span id="year"></span> UoG MRTS. All rights reserved.</div>
        </div>
    </footer>

    <script>
        // Simple animation for the form
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.forgot-container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                container.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>