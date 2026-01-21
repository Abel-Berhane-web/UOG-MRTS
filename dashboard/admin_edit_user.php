<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Get user info for navigation
require_once __DIR__ . '/notifications_functions.php';
$conn = new mysqli("localhost", "root", "", "test_uog");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get unread count for notifications
$unread_count = getUnreadCount($conn, $_SESSION['user_id']);

// Get user profile image
$stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($photo);
$stmt->fetch();
$stmt->close();

// Decide avatar
$avatar_type = $photo ? "image" : "icon";
$avatar_value = $photo ? "uploads/" . htmlspecialchars($photo) : "👤";

if (!isset($_GET['id'])) {
    die("User ID missing.");
}

$user_id = intval($_GET['id']);

// Handle update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $phone = $_POST['phone'];
    $telegram = $_POST['telegram'];
    $role = $_POST['role'];
    $specialization = ($role === 'technician') ? $_POST['specialization'] : null;
    $is_approved = isset($_POST['is_approved']) ? 1 : 0;
    $temp_password_flag = isset($_POST['temp_password_flag']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ?, username = ?, phone = ?, telegram = ?, role = ?, specialization = ?, is_approved = ?, temp_password_flag = ? WHERE id = ?");
    $stmt->bind_param("sssssssiii", $fullname, $email, $username, $phone, $telegram, $role, $specialization, $is_approved, $temp_password_flag, $user_id);
    $stmt->execute();
    header("Location: admin_manage_users.php?status=updated");
    exit;
}

// Fetch user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
if (!$user) die("User not found.");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Admin - UoG MRTS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin_edit_user.css"> 
    <style>
       
    </style>
</head>
<body>


    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-user-edit"></i> Edit User</h1>
                <p>Update user information and permissions</p>
            </div>
            
            <div class="edit-form-card">
                <form method="POST" id="userForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">User ID</label>
                            <input type="text" class="form-input" value="<?= $user['id'] ?>" disabled>
                        </div>
                        
                        <div class="form-group">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" id="username" name="username" class="form-input" value="<?= htmlspecialchars($user['username']) ?>" required>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="fullname" class="form-label">Full Name</label>
                            <input type="text" id="fullname" name="fullname" class="form-input" value="<?= htmlspecialchars($user['fullname']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-input" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" id="phone" name="phone" class="form-input" value="<?= htmlspecialchars($user['phone']) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="telegram" class="form-label">Telegram</label>
                            <input type="text" id="telegram" name="telegram" class="form-input" value="<?= htmlspecialchars($user['telegram']) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="role" class="form-label">Role</label>
                            <select id="role" name="role" class="form-select" required onchange="toggleSpecialization()">
                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="finance" <?= $user['role'] === 'finance' ? 'selected' : '' ?>>Finance</option>
                                <option value="technician" <?= $user['role'] === 'technician' ? 'selected' : '' ?>>Technician</option>
                                <option value="external_user" <?= $user['role'] === 'external_user' ? 'selected' : '' ?>>External User</option>
                            </select>
                        </div>
                        
                        <!-- Specialization (only for technicians) -->
                        <div class="form-group" id="specialization_div" style="display: <?= $user['role'] === 'technician' ? 'block' : 'none'; ?>;">
                            <label for="specialization" class="form-label">Specialization</label>
                            <input type="text" id="specialization" name="specialization" class="form-input" value="<?= htmlspecialchars($user['specialization']) ?>">
                        </div>
                        
                        <div class="form-group full-width">
                            <div class="checkbox-group">
                                <input type="checkbox" id="is_approved" name="is_approved" class="checkbox-input" <?= $user['is_approved'] ? 'checked' : '' ?>>
                                <label for="is_approved" class="checkbox-label">Account Approved</label>
                            </div>
                            
                            <div class="checkbox-group">
                                <input type="checkbox" id="temp_password_flag" name="temp_password_flag" class="checkbox-input" <?= $user['temp_password_flag'] ? 'checked' : '' ?>>
                                <label for="temp_password_flag" class="checkbox-label">Temporary Password Flag</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="admin_manage_users.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Users
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script>
        
        // Dynamic year for footer
        document.getElementById('year').textContent = new Date().getFullYear();
        
        // Toggle specialization field based on role
        function toggleSpecialization() {
            var role = document.getElementById('role').value;
            var specDiv = document.getElementById('specialization_div');
            specDiv.style.display = (role === 'technician') ? 'block' : 'none';
        }
        
        // Form validation
        const form = document.getElementById('userForm');
        form.addEventListener('submit', function(e) {
            const username = document.getElementById('username').value;
            if (!/^[A-Za-z0-9_]{3,20}$/.test(username)) {
                e.preventDefault();
                alert('Username must be 3-20 characters and can only contain letters, numbers, and underscores.');
            }
        });
    </script>
</body>
</html>