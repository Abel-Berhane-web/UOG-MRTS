<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'chief_technician') {
    header("Location: ../login.php");
    exit;
}
require_once __DIR__ . '/notifications_functions.php';

$conn = new mysqli("localhost", "root", "", "test_uog");
if ($conn->connect_error) die("DB connection failed: " . $conn->connect_error);

// Get unread count only
$unread_count = getUnreadCount($conn, $_SESSION['user_id']);

// Get chief technician statistics
// Requests awaiting assignment
$pending_assignment_query = "SELECT COUNT(*) as count FROM requests WHERE status = 'Pending Assignment'";
$pending_assignment_result = $conn->query($pending_assignment_query);
$pending_assignment = $pending_assignment_result->fetch_assoc()['count'];

// Requests in progress
$in_progress_query = "SELECT COUNT(*) as count FROM requests WHERE status = 'In Progress'";
$in_progress_result = $conn->query($in_progress_query);
$in_progress = $in_progress_result->fetch_assoc()['count'];

// Total technicians
$technicians_query = "SELECT COUNT(*) as count FROM users WHERE role = 'technician' AND is_approved = 1";
$technicians_result = $conn->query($technicians_query);
$technicians_count = $technicians_result->fetch_assoc()['count'];

// Completed requests this month
$completed_query = "SELECT COUNT(*) as count FROM requests 
                   WHERE status = 'Completed' 
                   AND MONTH(created_at) = MONTH(CURRENT_DATE())
                   AND YEAR(created_at) = YEAR(CURRENT_DATE())";
$completed_result = $conn->query($completed_query);
$completed_count = $completed_result->fetch_assoc()['count'];

// Recent requests for assignment
$recent_requests_query = "SELECT r.*, u.fullname 
                         FROM requests r
                         JOIN users u ON r.requested_by = u.id
                         WHERE r.status = 'Pending Assignment'
                         ORDER BY r.created_at DESC 
                         LIMIT 5";
$recent_requests_result = $conn->query($recent_requests_query);
$recent_requests = [];
while ($row = $recent_requests_result->fetch_assoc()) {
    $recent_requests[] = $row;
}

// Technician performance stats
$tech_performance_query = "SELECT u.fullname, 
                          COUNT(CASE WHEN r.status = 'Completed' THEN 1 END) as completed,
                          COUNT(CASE WHEN r.status = 'In Progress' THEN 1 END) as in_progress
                          FROM users u
                          LEFT JOIN requests r ON u.id = r.assigned_technician_id
                          WHERE u.role = 'technician' AND u.is_approved = 1
                          GROUP BY u.id
                          ORDER BY completed DESC
                          LIMIT 5";
$tech_performance_result = $conn->query($tech_performance_query);
$tech_performance = [];
while ($row = $tech_performance_result->fetch_assoc()) {
    $tech_performance[] = $row;
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
<title>Chief Technician Dashboard - UoG MRTS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="chief_technician_dashboard.css"> 
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
        transition: transform 0.3s, box-shadow 极速3s;
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
        border: 1px solid rgba极速255,255,255,.1);
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
        padding: 10极速 12px;
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
        font-size: 0.9极速;
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
    
    .section极速 h2 {
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
    
    .requests-list, .tech-list {
        display: grid;
        gap: 15px;
    }
    
    .request-item, .tech-item {
        padding: 15px;
        background: rgba(255,255,255,0.05);
        border-radius: 10px;
        border-left: 4px solid var(--green-apple);
        transition: transform 0.2s;
    }
    
    .request-item:hover, .tech-item:hover {
        transform: translateX(5px);
    }
    
    .request-item h4, .tech-item h4 {
        margin-bottom: 8px;
        color: var(--text);
    }
    
    .request-meta, .tech-meta {
        display: flex;
        justify-content: space-between;
        font-size: 0.9rem;
        color: var(--muted);
        margin-top: 10px;
    }
    
    .status-badge {
        padding: 4px 10px;
        border-radius: 20px;
        background: var(--green-dark);
        font-size: 0.8rem;
        display: inline-block;
    }
    
    .status-pending {
        background: #ffa502;
    }
    
    .status-inprogress {
        background: #2d98da;
    }
    
    .status-completed {
        background: #20bf6b;
    }
    
    .tech-stats {
        display: flex;
        gap: 15px;
        margin-top: 8px;
    }
    
    .tech-stat {
        background: rgba(255,255,255,0.1);
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.8rem;
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
        padding-top极速 16px;
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
    #极速Popup {
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
            grid-template-column极速: 1fr;
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
            <a href="chief_technician_dashboard.php" style="color: var(--green-apple); font-weight: bold;">Dashboard</a> 
            <a href="chief_technician.php">Assign Technician</a>
            <a href="chieftech_reports.php">Report</a>
            <a href="cheif_manage_technicians.php">Manage Technicians</a>
        </div>
        
        <div class="nav-right">
            <a href="notifications.php" class="notification-bell">
                 <i class="fas fa-b极速"></i>
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
                    <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
            <div class极速 welcome-text">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Chief Technician'); ?>!</h2>
                <p>Manage technician assignments and track request progress.</p>
            </div>
            <div class="welcome-actions">
                <a href="chief_technician.php" class="action-btn">Assign Technicians</a>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Awaiting Assignment</h3>
                <p class="stat-number"><?php echo $pending_assignment; ?></p>
                <p class="stat-desc">Requests need technicians</p>
            </div>
            <div class="stat-card">
                <h3>In Progress</h3>
                <p class="stat-number"><?php echo $in_progress; ?></p>
                <p class="stat-desc">Active requests</p>
            </div>
            <div class="stat-card">
                <h3>Technicians</h3>
                <p class="stat-number"><?php echo $technicians_count; ?></p>
                <p class="stat-desc">Available team members</p>
            </div>
            <div class="stat-card">
                <h3>Completed This Month</h3>
                <p class="stat-number"><?php echo $completed_count; ?></p>
                <p class="stat-desc">Successful resolutions</p>
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
                    <a href="chief_technician.php" class="action-btn">
                        <i class="fas fa-user-tag"></i>
                        <span>Assign Technicians</span>
                        <span class="action-info"><?php echo $pending_assignment; ?> pending</span>
                    </a>
                    <a href="cheif_manage_technicians.php" class="action-btn">
                        <i class="fas fa-users-cog"></i>
                        <span>Manage Technicians</span>
                    </a>
                    <a href="chieftech_reports.php" class="action-btn">
                        <i class="fas fa-chart-bar"></i>
                        <span>View Reports</span>
                    </a>
                    
                </div>
                
                <!-- Technician Performance -->
                <div class="section-header" style="margin-top: 30px;">
                    <h2>Top Technicians</h2>
                    <a href="performance.php" class="view-all">View All</a>
                </div>
                <div class="tech-list">
                    <?php if (!empty($tech_performance)): ?>
                        <?php foreach($tech_performance as $tech): ?>
                        <div class="tech-item">
                            <h4><?php echo htmlspecialchars($tech['fullname']); ?></h4>
                            <div class="tech-stats">
                                <span class="tech-stat" style="background: #20bf6b;">Completed: <?php echo $tech['completed']; ?></span>
                                <span class="tech-stat" style="background: #2d98da;">In Progress: <?php echo $tech['in_progress']; ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No technician data available.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Requests -->
            <div class="recent-section">
                <div class="section-header">
                    <h2>Recent Requests Needing Assignment</h2>
                    <a href="chief_technician.php" class="view-all">View All</a>
                </div>
                <div class="requests-list">
                    <?php if (!empty($recent_requests)): ?>
                        <?php foreach($recent_requests as $request): ?>
                        <div class="request-item">
                            <h4><?php echo htmlspecialchars($request['issue_title']); ?></h4>
                            <p>From: <?php echo htmlspecialchars($request['fullname']); ?></p>
                            <p>Category: <?php echo htmlspecialchars($request['category']); ?></p>
                            <div class="request-meta">
                                <span class="status-badge status-pending">Awaiting Assignment</span>
                                <span><?php echo date('M j, Y', strtotime($request['created_at'])); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No requests awaiting assignment.</p>
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
            <h3>Technician Links</h3>
            <a href="chief_technician_dashboard.php">Dashboard</a>
            <a href="chief_technician.php">Assign Technicians</a>
            <a href="cheif_manage_technicians.php">Manage Team</a>
        </div>
        <div class="muted">
            <h3>Resources</h3>
            <a href="chieftech_reports.php">Reports</a>
            <a href="performance.php">Performance</a>
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
    document.addEventListener('click', (e极速) => {
        if (aiPopup.classList.contains('show') && 
            !aiPopup.contains(e.target) && 
            e.target !== aiButton) {
            aiPopup.classList.remove('show');
        }
    });
</script>

</body>
</html>