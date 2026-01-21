<?php
// === admin_system_logs.php ===
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

// Handle delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_log_id'])) {
        $logId = intval($_POST['delete_log_id']);
        $stmt = $conn->prepare("DELETE FROM systemlogs WHERE logId = ?");
        $stmt->bind_param("i", $logId);
        $stmt->execute();
        $success_message = "Log entry deleted successfully.";
    } elseif (isset($_POST['delete_all'])) {
        $conn->query("TRUNCATE TABLE systemlogs");
        $success_message = "All logs have been deleted.";
    }
}

// Optional: Search by username or action
$search = '';
$params = [];
$types = '';
$sql = "
    SELECT s.logId, s.userId, u.username, s.action, s.ip_address, s.timestamp
    FROM systemlogs s
    LEFT JOIN users u ON s.userId = u.id
    WHERE 1
";

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = trim($_GET['search']);
    $sql .= " AND (u.username LIKE ? OR s.action LIKE ? OR s.ip_address LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "sss";
}

$sql .= " ORDER BY s.timestamp DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) die("Prepare failed: " . $conn->error);

if (!empty($params)) {
    // bind_param requires variables by reference
    $refs = [];
    foreach ($params as $key => $value) $refs[$key] = &$params[$key];
    array_unshift($refs, $types);
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>System Logs - UoG MRTS Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="admin_system_logs.css"> 
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
            <a href="admin_manage_users.php">Manage Accounts</a>
            <a href="admin_system_logs.php" style="color: var(--green-apple); font-weight: bold;">Login Record</a> 
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
            <h1 class="page-title">System Logs & Login Records</h1>
        </div>
    </div>
    
    <?php if (isset($success_message)): ?>
        <div class="alert">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    
    <!-- Search form -->
    <form method="GET" class="search-box">
        <input type="text" name="search" class="search-input" placeholder="Search by username, action, or IP address" value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if (!empty($search)): ?>
            <a href="admin_system_logs.php" class="btn btn-secondary">Reset</a>
        <?php endif; ?>
    </form>
    
    <!-- Logs table -->
    <div style="overflow-x: auto;">
        <table class="logs-table">
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>User ID</th>
                    <th>Username</th>
                    <th>Action</th>
                    <th>IP Address</th>
                    <th>Timestamp</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['logId']; ?></td>
                            <td><?php echo $row['userId']; ?></td>
                            <td><?php echo htmlspecialchars($row['username'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($row['action']); ?></td>
                            <td><span class="ip-address"><?php echo htmlspecialchars($row['ip_address']); ?></span></td>
                            <td><span class="timestamp"><?php echo htmlspecialchars($row['timestamp']); ?></span></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="delete_log_id" value="<?php echo $row['logId']; ?>">
                                    <button type="submit" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to delete this log entry?');">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <h3>No logs found</h3>
                                <p><?php echo !empty($search) ? 'Try a different search term' : 'No logs in the system'; ?></p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Danger zone for deleting all logs -->
    <div class="danger-zone">
        <h3>⚠️ Danger Zone</h3>
        <p>Permanently delete all system logs. This action cannot be undone.</p>
        <form method="POST" onsubmit="return confirm('WARNING: Are you absolutely sure you want to delete ALL logs? This action cannot be undone.');">
            <button type="submit" name="delete_all" class="btn btn-danger">🗑️ Delete All Logs</button>
        </form>
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

<!-- Floating AI button -->
<button id="aiButton">🤖</button>

<!-- AI chat popup -->
<div id="aiPopup">
    <iframe src="ai_widget.html"></iframe>
</div>

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
    
    // AI Chat functionality
    const aiButton = document.getElementById('aiButton');
    const aiPopup = document.getElementById('aiPopup');

    // Toggle popup on button click
    aiButton.addEventListener('click', () => {
        aiPopup.classList.toggle('show');
    });
    
    // Close popup when clicking outside
    document.addEventListener('click', (e) => {
        if (aiPopup.classList.contains('show') && 
            !aiPopup.contains(e.target) && 
            e.target !== aiButton) {
            aiPopup.classList.remove('show');
        }
    });
</script>

</body>
</html>
<?php
$stmt->close();
$conn->close();
?>