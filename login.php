<?php
session_start();
require __DIR__ . '/dashboard/email_functions.php';

// DB connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "test_uog";
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";

// Lockout configuration
$lockout_attempts = 5;
$lockout_minutes = 15;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = trim($_POST['username_or_email']);
    $password = $_POST['password'];

    // Fetch user
    $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username_or_email, $username_or_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $now = time();
        $last_failed = strtotime($user['last_failed_login'] ?? '1970-01-01 00:00:00');

        // Reset attempts if outside lockout period
        if (($now - $last_failed) > ($lockout_minutes * 60)) {
            $user['failed_attempts'] = 0;
        }

        // Check account status
        if ($user['account_status'] === 'disabled') {
            $error = "Your account has been disabled. Please contact the admin.";
        } 
        // Verify password
        elseif (password_verify($password, $user['password'])) {
            // Reset failed attempts
            $conn->query("UPDATE users SET failed_attempts = 0, last_failed_login = NULL WHERE id = " . intval($user['id']));

            // Check approval
            if ((int)$user['is_approved'] !== 1) {
                $error = "Your account is pending approval. Please wait for admin approval.";
            } 
            // Temporary password flag
            elseif ((int)$user['temp_password_flag'] !== 1) {
                $_SESSION['temp_user_id'] = $user['id'];
                header("Location: change_password.php");
                exit;
            } 
            // Normal login
            else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = trim($user['role']);
                $_SESSION['email'] = $user['email'];
                $_SESSION['fullname'] = $user['fullname'];

                // System log
                $action = "User logged in";
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $timestamp = date("Y-m-d H:i:s");
                $log_stmt = $conn->prepare("INSERT INTO systemlogs (userId, action, ip_address, timestamp) VALUES (?, ?, ?, ?)");
                $log_stmt->bind_param("isss", $user['id'], $action, $ip_address, $timestamp);
                $log_stmt->execute();
                $log_stmt->close();

                // Redirect based on role
                switch ($_SESSION['role']) {
                    case 'staff': header("Location: dashboard/staff.php"); break;
                    case 'external_user': header("Location: dashboard/external_dashboard.php"); break;
                    case 'technician': header("Location: dashboard/technician_dashboard.php"); break;
                    case 'admin': header("Location: dashboard/admin.php"); break;
                    case 'finance': header("Location: dashboard/finance.php"); break;
                    case 'chief_technician': header("Location: dashboard/chief_technician_dashboard.php"); break;
                    default:
                        $error = "Invalid user role.";
                        session_destroy();
                        break;
                }
                exit;
            }
        } 
        else {
            // Failed login → increment attempts
            $new_attempts = $user['failed_attempts'] + 1;

            // Update failed attempts and timestamp
            $update = $conn->prepare("UPDATE users SET failed_attempts = ?, last_failed_login = NOW() WHERE id = ?");
            $update->bind_param("ii", $new_attempts, $user['id']);
            $update->execute();
            $update->close();

            if ($new_attempts >= $lockout_attempts) {
                // Disable account and notify
                $conn->query("UPDATE users SET account_status = 'disabled' WHERE id = " . intval($user['id']));
                notifyAdminAccountLocked($conn, $user['username']);
                notifyUserAccountLocked($user['email'], $user['fullname']);
                $error = "Your account has been disabled after multiple failed login attempts. Contact admin.";
            } else {
                $error = "Incorrect password. Attempt $new_attempts of $lockout_attempts.";
            }
        }

    } else {
        $error = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - UoG MRTS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="login.css">
    <style>
      
    </style>
</head>
<body>
    <!-- NAVIGATION -->
    <nav class="nav">
        <div class="nav-inner">
            <div class="brand">
                <div class="brand-badge" aria-hidden="true">
                    <svg viewBox="0 0 24 24"></svg>
                </div>
                <span style="font-size: 30px; color: #2CB955; "><a href="home.html">UoG-MRTS</a></span>
            </div>
            
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="container">
            <div class="left-panel">
                
                <h1 class="welcome-text">Hello, <span></span></h1>
                <p class="subtitle">Register with your personal details to use all of the features of UoG MRTS maintenance tracking system.</p>
                
                <div class="features">
                    <div class="feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Track requests in real-time</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Get status updates</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Manage your profile</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Secure & reliable</span>
                    </div>
                </div>
                
                <a href="register.php" class="left-panel-btn">SIGN UP</a>
            </div>
            
            <div class="right-panel">
                <h2>Log In</h2>
                <p class="form-description">Login to access your UoG MRTS account</p>

                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" autocomplete="off">
                    <div class="form-group">
                        <label for="username_or_email">Username or Email</label>
                        <input type="text" id="username_or_email" name="username_or_email" placeholder="Enter your username or email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                    
                    <div class="remember-forgot">
                        <div class="remember">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Remember me</label>
                        </div>
                        <a href="forgot_password.php" class="forgot">Forgot password?</a>
                    </div>
                    
                    <button type="submit" class="btn">SIGN IN</button>
                </form>
                
                <div class="separator">or continue with</div>
                
                <div class="social-login">
                    <div class="social-btn" title="Telegram">
                        <i class="fab fa-telegram"></i>
                    </div>
                    <div class="social-btn" title="Microsoft">
                        <i class="fab fa-microsoft"></i>
                    </div>
                    <div class="social-btn" title="Instagram">
                        <i class="fab fa-instagram"></i>
                    </div>
                </div>
                
                <div class="signup-link">
                    Don't have an account? <a href="register.php">Sign up here</a>
                </div>
            </div>
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
        // Dynamic year
        document.getElementById('year').textContent = new Date().getFullYear();

        // Simple animation for the login form
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                container.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);

            // Add focus effects to inputs
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('focus', () => {
                    input.parentElement.style.transform = 'translateY(-2px)';
                });
                
                input.addEventListener('blur', () => {
                    input.parentElement.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>