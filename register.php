<?php
session_start();
require_once __DIR__ . '/dashboard/email_functions.php';
require_once __DIR__ . '/dashboard/notifications_functions.php';
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "test_uog";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$success = "";
$error = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = $_POST['fullname'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $telegram = $_POST['telegram'];
    $role = $_POST['role'];
    $specialization = isset($_POST['specialization']) ? $_POST['specialization'] : null;
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($telegram)) {
        $error = "Telegram username is required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check for duplicate username or email
        $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "Username or email already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Add is_approved = 0 for pending accounts
            $sql = "INSERT INTO users (fullname, username, email, phone, telegram, role, specialization, password, is_approved, temp_password_flag)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0,1)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssss", $fullname, $username, $email, $phone, $telegram, $role, $specialization, $hashed_password);

            if ($stmt->execute()) {
                $success = "Registration successful. Waiting for admin approval before you can log in.";

                $admin_email = "bellaberhan@gmail.com"; // Change this to your actual admin email
                notifyAdminNewRegistration($admin_email, $fullname, $username, $email, $role);
                notifyAdminNewRegistrationApp($conn, $fullname, $username, $role);

            } else {
                $error = "Error: " . $stmt->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - UoG MRTS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="register.css">
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
               
                <h1 class="welcome-text">Join <span>UoG MRTS</span></h1>
                <p class="subtitle">Create your account to access the Maintenance Request Tracking System</p>
                
                <div class="features">
                    <div class="feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Track maintenance requests</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Get real-time updates</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Manage your profile</span>
                    </div>
                    <div class="feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Secure & reliable platform</span>
                    </div>
                </div>
                
                <a href="login.php" class="left-panel-btn">LOGIN</a>
                <br><br>
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
            </div>
            
            <div class="right-panel">
                <div class="form-header">
                    <h2>Signup</h2>
                    <p class="form-description">Register to UoG-MRTS</p>

                    <?php if ($error): ?>
                        <div class="error-message">
                            <?php echo $error; ?>
                        </div>
                    <?php elseif ($success): ?>
                        <div class="success-message">
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-content">
                    <form method="POST" action="register.php" id="registrationForm" onsubmit="return validateForm()">
                        <div class="form-group">
                            <label for="fullname">Full Name</label>
                            <input type="text" id="fullname" name="fullname" placeholder="Enter your full name" required>
                            <span class="error" id="fullnameError"></span>
                        </div>
                        
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" placeholder="Choose a username" required>
                            <span class="error" id="usernameError"></span>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" placeholder="Enter your email" required>
                            <span class="error" id="emailError"></span>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone" placeholder="+2519XXXXXXXX" required>
                            <span class="error" id="phoneError"></span>
                        </div>
                        
                        <div class="form-group">
                            <label for="telegram">Telegram Username</label>
                            <input type="text" id="telegram" name="telegram" placeholder="Enter your Telegram username" required>
                            <span class="error" id="telegramError"></span>
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select name="role" id="role" required onchange="toggleSpec(this.value)" style="color: #ffffffff; background: #0f2016; textweight: bolder; ">
                                <option value=""> Select Role </option>
                                <option value="staff">Staff</option>
                                <option value="external_user">External User</option>
                                <option value="technician">Technician</option>
                                <option value="chief_technician">Chief Technician</option>
                                <option value="finance">Finance</option>
                                <option value="admin">Admin</option>
                            </select>
                            <span class="error" id="roleError"></span>
                        </div>
                        
                        <div class="form-group" id="spec_field" style="display:none;">
                            <label for="specialization">Specialization (for Technicians)</label>
                            <select name="specialization" id="specialization" style="color: #ffffffff; background: #0f2016; textweight: bolder; ">
                                <option value=""> Select Specialization </option>
                                <option value="Electrical">Electrical</option>
                                <option value="Networking">Networking</option>
                                <option value="Electronics">Electronics</option> 
                            </select>
                            <span class="error" id="specError"></span>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" placeholder="Create a password" required>
                            <span class="error" id="passwordError"></span>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                            <span class="error" id="confirmPasswordError"></span>
                        </div>
                        
                        <button type="submit" class="btn">REGISTER</button>
                    </form>
                    
                    <div class="login-link">
                        Already have an account? <a href="login.php">Login here</a>
                    </div>
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

        function toggleSpec(role) {
            const spec = document.getElementById("spec_field");
            spec.style.display = (role === "technician") ? "block" : "none";
            
            // Scroll to the specialization field when it appears
            if (role === "technician") {
                setTimeout(() => {
                    spec.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 300);
            }
        }

        function validateForm() {
            let isValid = true;

            // Clear all previous errors
            document.querySelectorAll('.error').forEach(el => el.textContent = '');

            const fullname = document.getElementById('fullname').value.trim();
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const telegram = document.getElementById('telegram').value.trim();
            const role = document.getElementById('role').value;
            const specialization = document.getElementById('specialization').value;
            const password = document.getElementById('password').value;
            const confirm_password = document.getElementById('confirm_password').value;

            // Full Name
            if (!/^[A-Za-z\s]+$/.test(fullname)) {
                document.getElementById('fullnameError').textContent = "Full Name can only contain letters and spaces.";
                isValid = false;
                
                // Scroll to the first error
                document.getElementById('fullname').scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            // Username
            if (!/^[A-Za-z0-9_]{3,20}$/.test(username)) {
                document.getElementById('usernameError').textContent = "Username must be 3-20 characters: letters, numbers, underscores.";
                isValid = false;
                
                if (isValid) {
                    document.getElementById('username').scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }

            // Email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                document.getElementById('emailError').textContent = "Please enter a valid email.";
                isValid = false;
                
                if (isValid) {
                    document.getElementById('email').scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }

            // Phone
            if (!/^\+251[0-9]{9}$/.test(phone)) {
                document.getElementById('phoneError').textContent = "Phone must start with +251 and have exactly 9 digits after it.";
                isValid = false;
                
                if (isValid) {
                    document.getElementById('phone').scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }

            // Telegram
            if (!/^[A-Za-z0-9_]{3,32}$/.test(telegram)) {
                document.getElementById('telegramError').textContent = "Telegram username must be 3-32 chars: letters, numbers, underscores.";
                isValid = false;
                
                if (isValid) {
                    document.getElementById('telegram').scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }

            // Role
            if (role === "") {
                document.getElementById('roleError').textContent = "Please select a role.";
                isValid = false;
                
                if (isValid) {
                    document.getElementById('role').scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }

            // Specialization
            if (role === "technician" && specialization === "") {
                document.getElementById('specError').textContent = "Please select a specialization for technician.";
                isValid = false;
                
                if (isValid) {
                    document.getElementById('specialization').scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }

            // Password
            if (password.length < 6) {
                document.getElementById('passwordError').textContent = "Password must be at least 6 characters.";
                isValid = false;
                
                if (isValid) {
                    document.getElementById('password').scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }

            // Confirm Password
            if (password !== confirm_password) {
                document.getElementById('confirmPasswordError').textContent = "Passwords do not match.";
                isValid = false;
                
                if (isValid) {
                    document.getElementById('confirm_password').scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }

            return isValid;
        }

        // Simple animation for the form
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.container');
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