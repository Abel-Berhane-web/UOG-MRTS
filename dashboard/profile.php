<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/notifications_functions.php';

$conn = new mysqli("localhost", "root", "", "test_uog");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get unread count
$unread_count = getUnreadCount($conn, $_SESSION['user_id']);

// Get user profile image
$user_id = $_SESSION['user_id'];
$sql = "SELECT id, username, fullname, email, phone, telegram, role, specialization, profile_image 
        FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("User not found.");
}

$user = $result->fetch_assoc();

// Decide avatar
$avatar_type = $user['profile_image'] ? "image" : "icon";
$avatar_value = $user['profile_image'] ? "uploads/" . htmlspecialchars($user['profile_image']) : "👤";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?= htmlspecialchars($user['username']) ?> - UoG MRTS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="profile.css"> 
    <style>
      
    </style>
</head>
<body>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="container">
            <div class="header">
            <a href="javascript:history.back()" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            
        </div>
            <div class="profile-header">
                <h1><i class="fas fa-user-circle"></i> Your Profile</h1>
                <p>Manage your account information and preferences</p>
            </div>
            
            <div class="profile-card">
                <div class="profile-image-section">
                    <?php if (!empty($user['profile_image'])): ?>
                        <img src="uploads/<?= htmlspecialchars($user['profile_image']) ?>" 
                             alt="Profile Image" class="profile-img">
                    <?php else: ?>
                        <div class="profile-img" style="background: linear-gradient(135deg, var(--green-apple), var(--green-dark)); display: flex; align-items: center; justify-content: center; font-size: 4rem;">
                            👤
                        </div>
                    <?php endif; ?>
                    
                    <a href="edit_profile.php" class="upload-btn">
                        <i class="fas fa-camera"></i> Change Photo
                    </a>
                </div>
                
                <div class="profile-details">
                    <div class="detail-group">
                        <span class="detail-label">Username</span>
                        <div class="detail-value"><?= htmlspecialchars($user['username']) ?></div>
                    </div>
                    
                    <div class="detail-group">
                        <span class="detail-label">Full Name</span>
                        <div class="detail-value"><?= htmlspecialchars($user['fullname']) ?></div>
                    </div>
                    
                    <div class="detail-group">
                        <span class="detail-label">Email</span>
                        <div class="detail-value">
                            <a href="mailto:<?= htmlspecialchars($user['email']) ?>">
                                <?= htmlspecialchars($user['email']) ?>
                            </a>
                        </div>
                    </div>
                    
                    <div class="detail-group">
                        <span class="detail-label">Phone</span>
                        <div class="detail-value"><?= htmlspecialchars($user['phone']) ?></div>
                    </div>
                    
                    <div class="detail-group">
                        <span class="detail-label">Telegram</span>
                        <div class="detail-value">
                            <?php if ($user['telegram']): ?>
                                <a href="https://t.me/<?= htmlspecialchars($user['telegram']) ?>" target="_blank">
                                    @<?= htmlspecialchars($user['telegram']) ?>
                                </a>
                            <?php else: ?>
                                <span style="color: var(--muted);">Not set</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="detail-group">
                        <span class="detail-label">Role</span>
                        <div class="detail-value">
                            <span class="role-badge"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $user['role']))) ?></span>
                        </div>
                    </div>
                    
                    <?php if ($user['role'] === 'technician' && !empty($user['specialization'])): ?>
                    <div class="detail-group">
                        <span class="detail-label">Specialization</span>
                        <div class="detail-value"><?= htmlspecialchars($user['specialization']) ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="detail-group">
                        <span class="detail-label">User ID</span>
                        <div class="detail-value">#<?= htmlspecialchars($user['id']) ?></div>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <a href="edit_profile.php" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>