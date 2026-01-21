<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'finance') {
    die("Unauthorized access.");
}

require_once __DIR__ . '/notifications_functions.php';

$conn = new mysqli("localhost", "root", "", "test_uog");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

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

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';


// Build query with filters
$sql = "
SELECT 
    p.id AS payment_id,
    p.request_id,
    r.issue_title,
    r.category,
    r.campus,
    r.room_number,
    r.price_status,
    u.fullname AS requester_name,
    u.email,
    u.phone,
    u.telegram,
    p.price,
    p.payment_instructions,
    p.payment_code,
    p.payment_screenshot_path,
    p.verified_status,
    p.created_at
FROM paymentproof p
JOIN requests r ON p.request_id = r.id
JOIN users u ON r.requested_by = u.id
WHERE 1=1
";

$params = array();
$types = "";

// Add status filter
if ($status_filter !== 'all') {
    $sql .= " AND p.verified_status = ?";
    $params[] = $status_filter;
    $types .= "s";
}



$sql .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Payment History - UoG MRTS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
      <link rel="stylesheet" href="finance_payment_history.css">   
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
                          <a href="finance.php">Dashboard</a> 
<a href="finance_pending_price.php">set price</a>
<a href="finance_payment_verification.php">verify payment</a>
<a href="finance_payment_history.php">payment history</a>
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
        <div class="container">
            <div class="dashboard-header">
                <h1> Finance Payment History</h1>
                <p>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>! Manage and review all payment records</p>
            </div>
            
            <!-- Filter buttons -->
            <div class="filters">
                <button class="filter-btn <?= $status_filter === 'all' ? 'active' : '' ?>" data-filter="all" data-type="status">All Payments</button>
                <button class="filter-btn <?= $status_filter === 'Pending' ? 'active' : '' ?>" data-filter="Pending" data-type="status">Pending</button>
                <button class="filter-btn <?= $status_filter === 'Waiting Payment' ? 'active' : '' ?>" data-filter="Waiting Payment" data-type="status">Waiting Payment</button>
                <button class="filter-btn <?= $status_filter === 'Pending Payment Verification' ? 'active' : '' ?>" data-filter="Pending Payment Verification" data-type="status">Pending Payment Verification</button>
                <button class="filter-btn <?= $status_filter === 'Verified' ? 'active' : '' ?>" data-filter="Verified" data-type="status">Verified</button>
                <button class="filter-btn <?= $status_filter === 'Rejected' ? 'active' : '' ?>" data-filter="Rejected" data-type="status">Rejected</button> 
            </div>
            
            <!-- Payment history table -->
            <?php if ($result->num_rows > 0): ?>
                <div class="payment-table-container">
                    <table class="payment-table">
                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Request ID</th>
                                <th>Request Title</th>
                                <th>Category</th>
                                <th>Requester</th>
                                <th>Price (ETB)</th>
                                <th>Payment Code</th>
                                <th>Screenshot</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): 
                                $status_class = 'status-' . strtolower($row['verified_status']);
                                $price_status_class = 'price-status-' . strtolower($row['price_status']);
                            ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($row['payment_id']) ?></td>
                                    <td>#<?= htmlspecialchars($row['request_id']) ?></td>
                                    <td><?= htmlspecialchars($row['issue_title']) ?></td>
                                    <td><?= htmlspecialchars($row['category']) ?></td>
                                    <td>
                                        <div class="requester-info">
                                            <div><strong><?= htmlspecialchars($row['requester_name']) ?></strong></div>
                                            <div><i class="fas fa-phone"></i> <?= htmlspecialchars($row['phone']) ?></div>
                                            
                                        </div>
                                    </td>
                                    <td><strong><?= number_format($row['price'], 2) ?></strong></td>
                                    <td>
                                        <?php if (!empty($row['payment_code'])): ?>
                                            <?= htmlspecialchars($row['payment_code']) ?>
                                        <?php else: ?>
                                            <span style="color: var(--muted);">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['payment_screenshot_path'])): ?>
                                            <a href="<?= htmlspecialchars($row['payment_screenshot_path']) ?>" target="_blank" class="view-link">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        <?php else: ?>
                                            <span style="color: var(--muted);">No file</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $status_class ?>">
                                            <?= htmlspecialchars($row['verified_status']) ?>
                                        </span>
                                    </td>
                                    
                                    <td><?= htmlspecialchars(date("M j, Y g:i A", strtotime($row['created_at']))) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <h3>No payment records found</h3>
                    <p><?php echo ($status_filter !== 'all' ) ? 'Try adjusting your filters' : 'No payment records available'; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        <div class="footer-inner">
           
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
                const isVisible = navLinks.style.display === 'flex';
                navLinks.style.display = isVisible ? 'none' : 'flex';
            });
        }
        
        // Filter functionality
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', function() {
                const filterType = this.getAttribute('data-type');
                const filterValue = this.getAttribute('data-filter');
                
                // Update URL parameters
                const url = new URL(window.location.href);
                url.searchParams.set(filterType, filterValue);
                
                // Navigate to the new URL
                window.location.href = url.toString();
            });
        });
        
        // Add animations to table rows
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.payment-table tbody tr');
            
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateX(20px)';
                
                setTimeout(() => {
                    row.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateX(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>