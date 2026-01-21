<?php
session_start();

// Only finance staff allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'finance') {
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

// Get finance statistics
// Requests needing pricing
$pricing_query = "SELECT COUNT(*) as count FROM requests 
                  WHERE price_status = 'Not Set' AND status != 'Completed'";
$pricing_result = $conn->query($pricing_query);
$pricing_needed = $pricing_result->fetch_assoc()['count'];

// Payments awaiting verification
$verification_query = "SELECT COUNT(*) as count FROM paymentproof 
                       WHERE verified_status IN ('Pending Payment Verification', 'Pending')";
$verification_result = $conn->query($verification_query);
$verification_needed = $verification_result->fetch_assoc()['count'];

// Recent payments for verification
$recent_payments_query = "SELECT pp.*, r.issue_title, u.fullname 
                          FROM paymentproof pp
                          JOIN requests r ON pp.request_id = r.id
                          JOIN users u ON r.requested_by = u.id
                          WHERE pp.verified_status IN ('Pending Payment Verification', 'Pending')
                          ORDER BY pp.created_at DESC 
                          LIMIT 5";
$recent_payments_result = $conn->query($recent_payments_query);
$recent_payments = [];
while ($row = $recent_payments_result->fetch_assoc()) {
    $recent_payments[] = $row;
}

// Payment statistics
$payment_stats_query = "SELECT 
    COUNT(*) as total_payments,
    SUM(CASE WHEN verified_status = 'Verified' THEN 1 ELSE 0 END) as verified,
    SUM(CASE WHEN verified_status IN ('Pending Payment Verification', 'Pending') THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN verified_status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN verified_status = 'Verified' THEN price ELSE 0 END) as total_revenue
FROM paymentproof";
$payment_stats_result = $conn->query($payment_stats_query);
$payment_stats = $payment_stats_result->fetch_assoc();

// Decide avatar
$avatar_type = $photo ? "image" : "icon";
$avatar_value = $photo ? "uploads/" . htmlspecialchars($photo) : "👤";
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Finance Dashboard - UoG MRTS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
      <link rel="stylesheet" href="finance.css">  
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
    
    .payments-list {
        display: grid;
        gap: 15px;
    }
    
    .payment-item {
        padding: 15px;
        background: rgba(255,255,255,0.05);
        border-radius: 10px;
        border-left: 4px solid var(--green-apple);
        transition: transform 0.2s;
    }
    
    .payment-item:hover {
        transform: translateX(5px);
    }
    
    .payment-item h4 {
        margin-bottom: 8px;
        color: var(--text);
    }
    
    .payment-meta {
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
    
    .status-verified {
        background: #20bf6b;
    }
    
    .status-rejected {
        background: #eb3b5a;
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
          <a href="finance.php">Dashboard</a> 
          <a href="finance_pending_price.php">Set Price</a>
          <a href="finance_payment_verification.php">Verify Payment</a>
          <a href="finance_payment_history.php">Payment History</a>
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
            <div class="welcome-text">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Finance Staff'); ?>!</h2>
                <p>Manage pricing and payment verification for maintenance requests.</p>
            </div>
            <div class="welcome-actions">
                <a href="finance_pending_price.php" class="action-btn">Set Prices</a>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Requests Needing Pricing</h3>
                <p class="stat-number"><?php echo $pricing_needed; ?></p>
                <p class="stat-desc">Awaiting cost estimation</p>
            </div>
            <div class="stat-card">
                <h3>Payments to Verify</h3>
                <p class="stat-number"><?php echo $verification_needed; ?></p>
                <p class="stat-desc">Awaiting verification</p>
            </div>
            <div class="stat-card">
                <h3>Total Revenue</h3>
                <p class="stat-number">Birr  <?php echo number_format($payment_stats['total_revenue'] ?? 0, 2); ?></p>
                <p class="stat-desc">From verified payments</p>
            </div>
            <div class="stat-card">
                <h3>Total Payments</h3>
                <p class="stat-number"><?php echo $payment_stats['total_payments'] ?? 0; ?></p>
                <p class="stat-desc">All payment records</p>
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
                    <a href="finance_pending_price.php" class="action-btn">
                        <i class="fas fa-tag"></i>
                        <span>Set Request Prices</span>
                        <span class="action-info"><?php echo $pricing_needed; ?> pending</span>
                    </a>
                    <a href="finance_payment_verification.php" class="action-btn">
                        <i class="fas fa-check-circle"></i>
                        <span>Verify Payments</span>
                        <span class="action-info"><?php echo $verification_needed; ?> pending</span>
                    </a>
                    <a href="finance_payment_history.php" class="action-btn">
                        <i class="fas fa-history"></i>
                        <span>Payment History</span>
                    </a>
                  
                </div>
                
                <!-- Payment Statistics -->
                <div class="section-header" style="margin-top: 30px;">
                    <h2>Payment Statistics</h2>
                </div>
                <div class="payment-stats">
                    <div class="payment-stat">
                        <span>Verified: </span>
                        <strong><?php echo $payment_stats['verified'] ?? 0; ?></strong>
                    </div>
                    <div class="payment-stat">
                        <span>Pending: </span>
                        <strong><?php echo $payment_stats['pending'] ?? 0; ?></strong>
                    </div>
                    <div class="payment-stat">
                        <span>Rejected: </span>
                        <strong><?php echo $payment_stats['rejected'] ?? 0; ?></strong>
                    </div>
                </div>
            </div>
            
            <!-- Recent Payments -->
            <div class="recent-section">
                <div class="section-header">
                    <h2>Recent Payments to Verify</h2>
                    <a href="finance_payment_verification.php" class="view-all">View All</a>
                </div>
                <div class="payments-list">
                    <?php if (!empty($recent_payments)): ?>
                        <?php foreach($recent_payments as $payment): 
                            $status_class = '';
                            if ($payment['verified_status'] == 'Pending Payment Verification') $status_class = 'status-pending';
                            if ($payment['verified_status'] == 'Verified') $status_class = 'status-verified';
                            if ($payment['verified_status'] == 'Rejected') $status_class = 'status-rejected';
                        ?>
                        <div class="payment-item">
                            <h4><?php echo htmlspecialchars($payment['issue_title']); ?></h4>
                            <p>Submitted by: <?php echo htmlspecialchars($payment['fullname']); ?></p>
                            <p>Amount: GHS <?php echo number_format($payment['price'], 2); ?></p>
                            <div class="payment-meta">
                                <span class="status-badge <?php echo $status_class; ?>"><?php echo $payment['verified_status']; ?></span>
                                <span><?php echo date('M j, Y', strtotime($payment['created_at'])); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No payments awaiting verification.</p>
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
            <h3>Finance Links</h3>
            <a href="finance.php">Dashboard</a>
            <a href="finance_pending_price.php">Set Prices</a>
            <a href="finance_payment_verification.php">Verify Payments</a>
        </div>
        <div class="muted">
            <h3>Help & Support</h3>
            <a href="#">Financial Guidelines</a>
            <a href="#">Reports</a>
            <a href="#">System Info</a>
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