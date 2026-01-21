<?php
// === admin_manage_users.php ===
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/notifications_functions.php';

$conn = new mysqli("localhost", "root", "", "test_uog");
if ($conn->connect_error) die("DB connection failed: " . $conn->connect_error);

// Get unread count only
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

// Handle deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    header("Location: admin_manage_users.php?status=deleted");
    exit;
}

// Handle search with prepared statement
$search_query = "";
$users = [];
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_param = "%" . trim($_GET['search']) . "%";
    $stmt = $conn->prepare("SELECT * FROM users WHERE username LIKE ? OR email LIKE ? OR fullname LIKE ? ORDER BY id ASC");
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();
    $search_query = htmlspecialchars(trim($_GET['search']));
} else {
    $result = $conn->query("SELECT * FROM users ORDER BY id ASC");
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users - UoG MRTS Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="admin_manage_users.css"> 
<style>
   
</style>
</head>
<body>

<!-- NAVIGATION BAR -->
<nav class="nav">
    <div class="nav-inner">
        <div class="brand">
            <div class="brand-badge" aria-hidden="true">
                <svg viewBox="0 0 24 24"></svg>
            </div>
            <span style="font-size: 30px; color: #2CB955;">UoG-MRTS</span>
        </div>
        
        <div class="nav-links">
            <a href="admin.php">Dashboard</a> 
            <a href="admin_approve_users.php">Give Permission</a> 
            <a href="admin_manage_users.php" style="color: var(--green-apple); font-weight: bold;">Manage Accounts</a>
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
    <div class="page-header">
        <div>
            <p class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Admin'); ?>!</p>
            <h1 class="page-title">Manage User Accounts</h1>
        </div>
    </div>
    
    <?php if (isset($_GET['status']) && $_GET['status'] === 'deleted'): ?>
        <div class="alert">
            User account has been successfully deleted.
        </div>
    <?php endif; ?>
    
    <!-- Search form -->
    <form method="GET" class="search-box">
        <input type="text" name="search" class="search-input" placeholder="Search by name, username or email" value="<?php echo $search_query; ?>">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if (!empty($search_query)): ?>
            <a href="admin_manage_users.php" class="btn btn-secondary">Reset</a>
        <?php endif; ?>
    </form>
    
    <!-- Users table -->
    <div style="overflow-x: auto;">
        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($users) > 0): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td>
                                <span class="role-badge <?php echo $user['role'] === 'admin' ? 'role-admin' : 'role-user'; ?>">
                                    <?php echo htmlspecialchars($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="admin_edit_user.php?id=<?php echo $user['id']; ?>" class="btn-action btn-edit">Update</a>
                                    <a href="admin_manage_users.php?delete_id=<?php echo $user['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <h3>No users found</h3>
                                <p><?php echo !empty($search_query) ? 'Try a different search term' : 'No users in the system'; ?></p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
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
    // Dynamic year for footer
    document.getElementById('year').textContent = new Date().getFullYear();
    
    // Mobile menu toggle
    const menuBtn = document.getElementById('menuBtn');
    const navLinks = document.querySelector('.nav-links');
    if (menuBtn && navLinks) {
        menuBtn.addEventListener('click', () => {
            const shown = navLinks.style.display === 'flex';
            navLinks.style.display = shown ? 'none' : 'flex';
        });
    }
    
    
</script>

</body>
</html>
<?php $conn->close(); ?>