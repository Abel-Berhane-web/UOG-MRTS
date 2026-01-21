<?php
session_start();

// Set secure session parameters
/*session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
*/
// Only temporary password users can access this page
if (!isset($_SESSION['temp_user_id'])) {
    header("Location: login.php");
    exit;
}
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "test_uog";
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";
$message_type = ""; // success or error

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
        $message_type = "error";
    } else if (strlen($new_password) < 8) {
        $message = "Password must be at least 8 characters long.";
        $message_type = "error";
    } else if (!preg_match('/[A-Z]/', $new_password)) {
        $message = "Password must contain at least one uppercase letter.";
        $message_type = "error";
    } else if (!preg_match('/[a-z]/', $new_password)) {
        $message = "Password must contain at least one lowercase letter.";
        $message_type = "error";
    } else if (!preg_match('/[0-9]/', $new_password)) {
        $message = "Password must contain at least one number.";
        $message_type = "error";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password and set temp_password_flag = 1
        $stmt = $conn->prepare("UPDATE users SET password = ?, temp_password_flag = 1 WHERE id = ?");
        $stmt->bind_param("si", $hashed, $_SESSION['temp_user_id']);
        
        if ($stmt->execute()) {
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Fetch the user's role from DB
            $stmt2 = $conn->prepare("SELECT role, username, email, fullname FROM users WHERE id = ?");
            $stmt2->bind_param("i", $_SESSION['temp_user_id']);
            $stmt2->execute();
            $result = $stmt2->get_result();
            $user = $result->fetch_assoc();
            $role = trim($user['role']);

            // Set normal session and clear temp session
            $_SESSION['user_id'] = $_SESSION['temp_user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role'] = $role;
            
            // System log
            $action = "User changed temporary password";
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $timestamp = date("Y-m-d H:i:s");
            $log_stmt = $conn->prepare("INSERT INTO systemlogs (userId, action, ip_address, timestamp) VALUES (?, ?, ?, ?)");
            $log_stmt->bind_param("isss", $_SESSION['user_id'], $action, $ip_address, $timestamp);
            $log_stmt->execute();
            $log_stmt->close();
            
            unset($_SESSION['temp_user_id']);

            // Redirect based on role
            switch ($role) {
                case 'staff': $redirect = "dashboard/staff.php"; break;
                case 'external_user': $redirect = "dashboard/external_user.php"; break;
                case 'technician': $redirect = "dashboard/technician.php"; break;
                case 'chief_technician': $redirect = "dashboard/chief_technician.php"; break;
                case 'admin': $redirect = "dashboard/admin.php"; break;
                case 'finance': $redirect = "dashboard/finance.php"; break;
                default: $redirect = "login.php"; break;
            }
            
            // Success message for the next page
            $_SESSION['success_message'] = "Password changed successfully!";
            header("Location: $redirect");
            exit;
        } else {
            $message = "Failed to update password. Please try again.";
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - UoG MRTS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="change_password.css">
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
    <div class="main-content">
        <div class="change-container">
            <h2>Change Your Password</h2>
            <p class="form-description">Please set a new secure password for your account</p>

            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" autocomplete="off" id="passwordForm">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" placeholder="Enter your new password" required>
                    
                    <div class="password-strength">
                        <div class="requirement unmet" id="lengthReq">
                            <i class="fas fa-times"></i> At least 8 characters
                        </div>
                        <div class="requirement unmet" id="uppercaseReq">
                            <i class="fas fa-times"></i> One uppercase letter
                        </div>
                        <div class="requirement unmet" id="lowercaseReq">
                            <i class="fas fa-times"></i> One lowercase letter
                        </div>
                        <div class="requirement unmet" id="numberReq">
                            <i class="fas fa-times"></i> One number
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your new password" required>
                    <div id="confirmMessage" style="margin-top: 8px; font-size: 0.9rem;"></div>
                </div>
                
                <button type="submit" class="btn" id="submitBtn" disabled>CHANGE PASSWORD</button>
            </form>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        <div class="footer-inner">
            <div>
                <div class="brand" style="margin-bottom: 10px">
                    <span style="font-size: 30px; color: #2CB955;">UoG-MRTS</span>
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
            <div class="credits">© <?php echo date('Y'); ?> UoG MRTS. All rights reserved.</div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.change-container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                container.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
            
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            const confirmMessage = document.getElementById('confirmMessage');
            const submitBtn = document.getElementById('submitBtn');
            
            // Password requirement elements
            const lengthReq = document.getElementById('lengthReq');
            const uppercaseReq = document.getElementById('uppercaseReq');
            const lowercaseReq = document.getElementById('lowercaseReq');
            const numberReq = document.getElementById('numberReq');
            
            function validatePassword() {
                const password = newPassword.value;
                let isValid = true;
                
                // Check length
                if (password.length >= 8) {
                    lengthReq.classList.remove('unmet');
                    lengthReq.classList.add('met');
                    lengthReq.innerHTML = '<i class="fas fa-check"></i> At least 8 characters';
                } else {
                    lengthReq.classList.remove('met');
                    lengthReq.classList.add('unmet');
                    lengthReq.innerHTML = '<i class="fas fa-times"></i> At least 8 characters';
                    isValid = false;
                }
                
                // Check uppercase
                if (/[A-Z]/.test(password)) {
                    uppercaseReq.classList.remove('unmet');
                    uppercaseReq.classList.add('met');
                    uppercaseReq.innerHTML = '<i class="fas fa-check"></i> One uppercase letter';
                } else {
                    uppercaseReq.classList.remove('met');
                    uppercaseReq.classList.add('unmet');
                    uppercaseReq.innerHTML = '<i class="fas fa-times"></i> One uppercase letter';
                    isValid = false;
                }
                
                // Check lowercase
                if (/[a-z]/.test(password)) {
                    lowercaseReq.classList.remove('unmet');
                    lowercaseReq.classList.add('met');
                    lowercaseReq.innerHTML = '<i class="fas fa-check"></i> One lowercase letter';
                } else {
                    lowercaseReq.classList.remove('met');
                    lowercaseReq.classList.add('unmet');
                    lowercaseReq.innerHTML = '<i class="fas fa-times"></i> One lowercase letter';
                    isValid = false;
                }
                
                // Check number
                if (/[0-9]/.test(password)) {
                    numberReq.classList.remove('unmet');
                    numberReq.classList.add('met');
                    numberReq.innerHTML = '<i class="fas fa-check"></i> One number';
                } else {
                    numberReq.classList.remove('met');
                    numberReq.classList.add('unmet');
                    numberReq.innerHTML = '<i class="fas fa-times"></i> One number';
                    isValid = false;
                }
                
                // Check if passwords match
                if (confirmPassword.value) {
                    if (password === confirmPassword.value) {
                        confirmMessage.style.color = '#a3e9b3';
                        confirmMessage.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match';
                    } else {
                        confirmMessage.style.color = '#ffb3b3';
                        confirmMessage.innerHTML = '<i class="fas fa-times-circle"></i> Passwords do not match';
                        isValid = false;
                    }
                } else {
                    confirmMessage.innerHTML = '';
                }
                
                // Enable/disable submit button
                submitBtn.disabled = !isValid || !confirmPassword.value || password !== confirmPassword.value;
                
                return isValid;
            }
            
            newPassword.addEventListener('input', validatePassword);
            confirmPassword.addEventListener('input', validatePassword);
        });
    </script>
</body>
</html>