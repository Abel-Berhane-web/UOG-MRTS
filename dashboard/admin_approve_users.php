<?php
session_start();
require_once __DIR__ . '/email_functions.php';

// Only allow admins
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access.");
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

// DB connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "test_uog";

// Approve user
if (isset($_GET['approve'])) {
    $id = intval($_GET['approve']);
    $user_info_stmt = $conn->prepare("SELECT email, fullname FROM users WHERE id = ?");
    $user_info_stmt->bind_param("i", $id);
    $user_info_stmt->execute();
    $result_info = $user_info_stmt->get_result();
    $user_info = $result_info->fetch_assoc();
    $user_info_stmt->close();

    if ($user_info) {
        $stmt = $conn->prepare("UPDATE users SET is_approved = 1, account_status='enabled' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        notifyUserAccountApproved($user_info['email'], $user_info['fullname']);
    }

    header("Location: admin_approve_users.php");
    exit;
}

// Reject user (delete)
if (isset($_GET['reject'])) {
    $id = intval($_GET['reject']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_approve_users.php");
    exit;
}

// Toggle account status (enable/disable)
if (isset($_GET['toggle_status'])) {
    $id = intval($_GET['toggle_status']);

    // Get current status
    $status_stmt = $conn->prepare("SELECT account_status FROM users WHERE id = ?");
    $status_stmt->bind_param("i", $id);
    $status_stmt->execute();
    $status_result = $status_stmt->get_result();
    $user = $status_result->fetch_assoc();
    $status_stmt->close();

    if ($user) {
        $new_status = ($user['account_status'] === 'enabled') ? 'disabled' : 'enabled';
        $update_stmt = $conn->prepare("UPDATE users SET account_status = ? WHERE id = ?");
        $update_stmt->bind_param("si", $new_status, $id);
        $update_stmt->execute();
        $update_stmt->close();
    }

    header("Location: admin_approve_users.php");
    exit;
}

// Fetch all users (approved and unapproved)
$result = $conn->query("SELECT id, username, fullname, email, phone, telegram, role, specialization, is_approved, account_status 
                        FROM users ORDER BY is_approved ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - User Management - UoG MRTS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link rel="stylesheet" href="admin_approve_users.css"> 
    <style>
       
    </style>
</head>
<body>
    <!-- NAVIGATION BAR -->
    <nav class="nav">
        <div class="nav-inner">
            <div class="brand">
                <span>UoG-MRTS</span>
            </div>
            
            <div class="nav-links">
                <a href="admin.php">Dashboard</a> 
                <a href="admin_approve_users.php" style="color: var(--green-apple); font-weight: bold;">Give Permission</a>
                <a href="admin_manage_users.php">Manage Accounts</a>
                <a href="admin_system_logs.php">Login Record</a>
            </div>
            
            <div class="nav-right">
                <a href="notifications.php" class="notification-bell">
                     <i class="fas fa-bell"></i>
                    <?php if($unread_count > 0): ?>
                        <span class="notification-count"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
                
                <div class="profile-container">
                    <?php if ($avatar_type === "image"): ?>
                        <img src="<?php echo $avatar_value; ?>" alt="Profile" class="profile-pic">
                    <?php else: ?>
                        <div class="profile-icon"><?php echo $avatar_value; ?></div>
                    <?php endif; ?>

                    <div class="dropdown">
                        <a href="profile.php">Profile</a>
                        <a href="../logout.php">Logout</a>
                    </div>
                </div>
                
                <button class="menu-btn" id="menuBtn" aria-label="Toggle menu">☰</button>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-users-cog"></i> User Management</h1>
                <p>Approve, manage, and monitor all system users</p>
            </div>
            
            <div class="users-table-container">
                <?php if ($result->num_rows > 0): ?>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Telegram</th>
                                <th>Role</th>
                                <th>Specialization</th>
                                <th>Status</th>
                                <th>Account</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($row['telegram']); ?></td>
                                    <td><?php echo htmlspecialchars($row['role']); ?></td>
                                    <td><?php echo htmlspecialchars($row['specialization']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $row['is_approved'] ? 'status-approved' : 'status-pending'; ?>">
                                            <?php echo $row['is_approved'] ? 'Approved' : 'Pending'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $row['account_status'] === 'enabled' ? 'status-approved' : 'status-disabled'; ?>">
                                            <?php echo ucfirst($row['account_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                            <?php if (!$row['is_approved']): ?>
                                                <a href="?approve=<?php echo $row['id']; ?>" class="action-btn btn-approve">
                                                    <i class="fas fa-check"></i> Approve
                                                </a>
                                                <a href="?reject=<?php echo $row['id']; ?>" class="action-btn btn-reject" onclick="return confirm('Are you sure you want to reject this user? This action cannot be undone.');">
                                                    <i class="fas fa-times"></i> Reject
                                                </a>
                                            <?php endif; ?>
                                            <a href="?toggle_status=<?php echo $row['id']; ?>" class="action-btn btn-toggle">
                                                <i class="fas fa-power-off"></i> <?php echo ($row['account_status'] === 'enabled') ? 'Disable' : 'Enable'; ?>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-users">
                        <i class="fas fa-users-slash"></i>
                        <h3>No users found</h3>
                        <p>There are no users in the system yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        <div class="footer-inner">
            
            <div>   
            </div>
            <div class="credits">© <span id="year"></span> UoG MRTS. All rights reserved.</div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        const menuBtn = document.getElementById('menuBtn');
        const navLinks = document.querySelector('.nav-links');
        
        if (menuBtn && navLinks) {
            menuBtn.addEventListener('click', () => {
                const isVisible = navLinks.style.display === 'flex';
                navLinks.style.display = isVisible ? 'none' : 'flex';
            });
        }
        
        // Dynamic year for footer
        document.getElementById('year').textContent = new Date().getFullYear();
    </script>
</body>
</html>