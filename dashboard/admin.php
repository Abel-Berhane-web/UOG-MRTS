<?php
// === admin.php ===
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

// Get admin statistics
// Users awaiting approval
$pending_users_query = "SELECT COUNT(*) as count FROM users WHERE is_approved = 0";
$pending_users_result = $conn->query($pending_users_query);
$pending_users = $pending_users_result->fetch_assoc()['count'];

// Total users
$total_users_query = "SELECT COUNT(*) as count FROM users";
$total_users_result = $conn->query($total_users_query);
$total_users = $total_users_result->fetch_assoc()['count'];

// Total requests
$total_requests_query = "SELECT COUNT(*) as count FROM requests";
$total_requests_result = $conn->query($total_requests_query);
$total_requests = $total_requests_result->fetch_assoc()['count'];

// Recent system logs
$recent_logs_query = "SELECT sl.*, u.fullname 
                      FROM systemlogs sl 
                      JOIN users u ON sl.userId = u.id 
                      ORDER BY sl.timestamp DESC 
                      LIMIT 5";
$recent_logs_result = $conn->query($recent_logs_query);
$recent_logs = [];
while ($row = $recent_logs_result->fetch_assoc()) {
    $recent_logs[] = $row;
}

// User role distribution
$role_distribution_query = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
$role_distribution_result = $conn->query($role_distribution_query);
$role_distribution = [];
while ($row = $role_distribution_result->fetch_assoc()) {
    $role_distribution[] = $row;
}

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - UoG MRTS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="admin.css"> 
<style>
    :root {
        --green-apple: #2CB955;
        --green-dark: #124F29;
        --bg: #0b1510;
        --text: #eaf7ee;
        --muted: #bfe9ca;
        --card: #0f2016;
        --shadow: 0 10px 30px rgba(0,0,0,.35);
        --radius: 16px;
    }
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    body {
        background: linear-gradient(135deg, var(--bg), #0f1a13);
        color: var(--text);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    
    /* Navigation styles */
    .nav {
        position: sticky;
        top: 0;
        z-index: 1000;
        backdrop-filter: saturate(140%) blur(8px);
        background: linear-gradient(180deg, rgba(18,79,41,.9), rgba(18,79,41,.65));
        border-bottom: 1px solid rgba(255,255,255,.06);
    }
    
    .nav-inner {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        gap: 16px;
        align-items: center;
        justify-content: space-between;
        padding: 14px 20px;
    }
    
    .brand {
        display: flex;
        gap: 10px;
        align-items: center;
        font-weight: 800;
        letter-spacing: .5px;
    }
    
    
    .brand-badge svg {
        width: 24px;
        height: 24px;
        fill: white;
    }
    
    .nav-links {
        display: flex;
        gap: 22px;
        align-items: center;
    }
    
    .nav-links a {
        color: var(--text);
        text-decoration: none;
        opacity: .9;
        transition: opacity 0.2s ease;
        padding: 8px 12px;
        border-radius: 6px;
    }
    
    .nav-links a:hover {
        opacity: 1;
        color: var(--green-apple);
    }
    
    .nav-right {
        display: flex;
        gap: 15px;
        align-items: center;
    }
    
    .notification-bell {
        position: relative;
        color: var(--text);
        text-decoration: none;
        font-size: 1.4rem;
        padding: 8px;
        border-radius: 50%;
        transition: background 0.3s;
    }
    
    .notification-bell:hover {
        background: #2CB955;
    }
    
    .notification-count {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #ff4757;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        font-weight: bold;
    }
    
    .profile-container {
        position: relative;
        display: flex;
        align-items: center;
    }
    
    .profile-pic {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        cursor: pointer;
        border: 2px solid var(--green-apple);
        transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .profile-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 1.2rem;
        border: 2px solid var(--green-apple);
        transition: transform 0.3s, box-shadow 0.3s;
    }
    
    /* Hover effect only for profile image/icon */
    .profile-pic:hover, .profile-icon:hover {
        transform: scale(1.1);
        box-shadow: 0 0 0 3px rgba(44, 185, 85, 0.3);
    }
    
    .dropdown {
        position: absolute;
        top: 50px;
        right: 0;
        background: var(--card);
        border: 1px solid rgba(255,255,255,.1);
        border-radius: var(--radius);
        padding: 10px;
        display: none;
        flex-direction: column;
        min-width: 120px;
        box-shadow: var(--shadow);
        z-index: 100;
    }
    
    .dropdown a {
        color: var(--text);
        text-decoration: none;
        padding: 8px 12px;
        border-radius: 6px;
        transition: background 0.3s;
    }
    
    .dropdown a:hover {
        color: var(--green-apple);
        font-weight: bolder;
    }
    
    .profile-container:hover .dropdown {
        display: flex;
    }
    
    .menu-btn {
        display: none;
        border: 1px solid rgba(255,255,255,.15);
        padding: 10px 12px;
        border-radius: 10px;
        background: transparent;
        cursor: pointer;
        color: var(--text);
    }
    
    /* Main content area */
    .main-content {
        flex: 1;
        padding: 40px 20px;
    }
    
    .dashboard-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .welcome-banner {
        background: linear-gradient(135deg, var(--green-dark), var(--green-apple));
        padding: 25px;
        border-radius: var(--radius);
        margin-bottom: 30px;
        box-shadow: var(--shadow);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .welcome-text h2 {
        font-size: 1.8rem;
        margin-bottom: 10px;
    }
    
    .welcome-text p {
        opacity: 0.9;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: var(--card);
        padding: 20px;
        border-radius: var(--radius);
        text-align: center;
        box-shadow: var(--shadow);
        transition: transform 0.3s;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-card h3 {
        font-size: 1rem;
        margin-bottom: 15px;
        color: var(--muted);
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: bold;
        color: var(--green-apple);
        margin: 10px 0;
    }
    
    .stat-desc {
        font-size: 0.9rem;
        color: var(--muted);
    }
    
    .dashboard-sections {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }
    
    .action-section, .recent-section {
        background: var(--card);
        padding: 25px;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
    }
    
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    .section-header h2 {
        font-size: 1.4rem;
    }
    
    .view-all {
        color: var(--green-apple);
        text-decoration: none;
        font-size: 0.9rem;
    }
    
    .action-buttons {
        display: grid;
        gap: 15px;
    }
    
    .action-btn {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        background: var(--green-dark);
        color: white;
        text-decoration: none;
        border-radius: var(--radius);
        transition: transform 0.3s, background 0.3s;
    }
    
    .action-btn:hover {
        background: var(--green-apple);
        transform: translateX(5px);
    }
    
    .action-btn i {
        font-size: 1.5rem;
        width: 30px;
        text-align: center;
    }
    
    .action-info {
        margin-left: auto;
        background: rgba(255,255,255,0.1);
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.9rem;
    }
    
    .logs-list {
        display: grid;
        gap: 15px;
    }
    
    .log-item {
        padding: 15px;
        background: rgba(255,255,255,0.05);
        border-radius: 10px;
        border-left: 4px solid var(--green-apple);
        transition: transform 0.2s;
    }
    
    .log-item:hover {
        transform: translateX(5px);
    }
    
    .log-item h4 {
        margin-bottom: 8px;
        color: var(--text);
        display: flex;
        justify-content: space-between;
    }
    
    .log-meta {
        font-size: 0.9rem;
        color: var(--muted);
        margin-top: 10px;
    }
    
    .log-action {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 100%;
    }
    
    .role-distribution {
        margin-top: 20px;
    }
    
    .role-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    
    .role-item:last-child {
        border-bottom: none;
    }
    
    /* Footer styles */
    footer {
        border-top: 1px solid rgba(255,255,255,.06);
        background: #0a140f;
        padding: 30px 20px;
        margin-top: 50px;
    }
    
    .footer-inner {
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 2fr 1fr 1fr;
        gap: 20px;
    }
    
    .brand-footer {
        display: flex;
        gap: 10px;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .credits {
        grid-column: 1/-1;
        opacity: .7;
        font-size: 14px;
        text-align: center;
        padding-top: 16px;
    }
    
    .muted {
        color: var(--muted);
    }
    
    .muted a {
        color: var(--muted);
        text-decoration: none;
        transition: color 0.3s;
        display: block;
        margin-top: 10px;
    }
    
    .muted a:hover {
        color: var(--green-apple);
    }
    
    /* Floating AI button */
    #aiButton {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--green-apple), var(--green-dark));
        color: white;
        border: none;
        cursor: pointer;
        font-size: 28px;
        box-shadow: var(--shadow);
        z-index: 1000;
        transition: transform 0.2s;
    }
    
    #aiButton:hover { 
        transform: scale(1.1); 
        box-shadow: 0 6px 20px rgba(44,185,85,0.4);
    }

    /* AI chat popup container */
    #aiPopup {
        position: fixed;
        bottom: 90px;
        right: 20px;
        width: 400px;
        height: 500px;
        background: transparent;
        border: none;
        border-radius: 20px;
        box-shadow: var(--shadow);
        display: none;
        overflow: hidden;
        z-index: 1000;
        transition: opacity 0.3s ease;
    }

    #aiPopup.show {
        display: block;
        opacity: 1;
    }

    /* iframe fills the popup */
    #aiPopup iframe {
        width: 100%;
        height: 100%;
        border: none;
        border-radius: var(--radius);
        background: transparent;
    }
    
    @media (max-width: 968px) {
        .dashboard-sections {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 768px) {
        .nav-links {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: rgba(11,21,16,.95);
            border: 1px solid rgba(255,255,255,.06);
            border-radius: 12px;
            padding: 12px 16px;
            flex-direction: column;
            gap: 12px;
        }
        
        .menu-btn {
            display: inline-flex;
        }
        
        .footer-inner {
            grid-template-columns: 1fr;
            text-align: center;
        }
        
        #aiPopup {
            width: 90%;
            right: 5%;
            left: 5%;
        }
        
        .welcome-banner {
            flex-direction: column;
            text-align: center;
            gap: 15px;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
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
          <a href="admin.php" style="color: var(--green-apple); font-weight: bold;">Dashboard</a> 
          <a href="admin_approve_users.php">Give Permission</a> 
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
    <div class="dashboard-container">
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="welcome-text">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Admin'); ?>!</h2>
                <p>System administration and user management dashboard.</p>
            </div>
            <div class="welcome-actions">
                <a href="admin_approve_users.php" class="action-btn">Approve Users</a>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Users Awaiting Approval</h3>
                <p class="stat-number"><?php echo $pending_users; ?></p>
                <p class="stat-desc">Need permission review</p>
            </div>
            <div class="stat-card">
                <h3>Total Users</h3>
                <p class="stat-number"><?php echo $total_users; ?></p>
                <p class="stat-desc">All system users</p>
            </div>
            <div class="stat-card">
                <h3>Total Requests</h3>
                <p class="stat-number"><?php echo $total_requests; ?></p>
                <p class="stat-desc">All maintenance requests</p>
            </div>
            <div class="stat-card">
                <h3>System Logs</h3>
                <p class="stat-number"><?php echo count($recent_logs); ?>+</p>
                <p class="stat-desc">Recent activities</p>
            </div>
        </div>
        
        <!-- Dashboard Sections -->
        <div class="dashboard-sections">
            <!-- Quick Actions -->
            <div class="action-section">
                <div class="section-header">
                    <h2>Quick Actions</h2>
                </div>
                <div class="action-buttons">
                    <a href="admin_approve_users.php" class="action-btn">
                        <i class="fas fa-user-check"></i>
                        <span>Approve Users</span>
                        <span class="action-info"><?php echo $pending_users; ?> pending</span>
                    </a>
                    <a href="admin_manage_users.php" class="action-btn">
                        <i class="fas fa-users-cog"></i>
                        <span>Manage Users</span>
                    </a>
                    <a href="admin_system_logs.php" class="action-btn">
                        <i class="fas fa-clipboard-list"></i>
                        <span>View System Logs</span>
                    </a>
                   
                </div>
                
                <!-- Role Distribution -->
                <div class="section-header" style="margin-top: 30px;">
                    <h2>User Role Distribution</h2>
                </div>
                <div class="role-distribution">
                    <?php foreach($role_distribution as $role): ?>
                    <div class="role-item">
                        <span><?php echo ucfirst($role['role']); ?></span>
                        <span><?php echo $role['count']; ?> users</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Recent Activities -->
            <div class="recent-section">
                <div class="section-header">
                    <h2>Recent System Activities</h2>
                    <a href="admin_system_logs.php" class="view-all">View All</a>
                </div>
                <div class="logs-list">
                    <?php if (!empty($recent_logs)): ?>
                        <?php foreach($recent_logs as $log): ?>
                        <div class="log-item">
                            <h4>
                                <span><?php echo htmlspecialchars($log['fullname']); ?></span>
                                <span><?php echo date('M j, g:i a', strtotime($log['timestamp'])); ?></span>
                            </h4>
                            <div class="log-action" title="<?php echo htmlspecialchars($log['action']); ?>">
                                <?php echo htmlspecialchars($log['action']); ?>
                            </div>
                            <div class="log-meta">
                                IP: <?php echo $log['ip_address']; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No recent system activities.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FOOTER -->
<footer>
    <div class="footer-inner">
        <div>
            <div class="brand-footer">
                <span style="font-size: 24px; color: #2CB955;">UoG-MRTS</span>
            </div>
            <p class="muted">University of Ghana Maintenance Request Tracking System</p>
        </div>
        <div class="muted">
            <h3>Admin Links</h3>
            <a href="admin.php">Dashboard</a>
            <a href="admin_approve_users.php">Approve Users</a>
            <a href="admin_manage_users.php">Manage Users</a>
        </div>
        <div class="muted">
            <h3>System</h3>
            <a href="admin_system_logs.php">System Logs</a>
            <a href="system_settings.php">Settings</a>
            <a href="#">Help Center</a>
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