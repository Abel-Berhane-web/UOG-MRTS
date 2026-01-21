<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'external_user') {
    die("Unauthorized access.");
}

require_once __DIR__ . '/notifications_functions.php';

$conn = new mysqli("localhost", "root", "", "test_uog");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$user_id = $_SESSION['user_id'];

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
$payment_method_filter = $_GET['payment_method'] ?? 'all';

// Build query with filters
$query = "
    SELECT 
        p.id,
        p.request_id,
        r.issue_title,
        p.price,
        p.payment_instructions,
        p.payment_code,
        p.payment_screenshot_path,
        p.verified_status,
        p.created_at,
        CASE 
            WHEN p.payment_code IS NOT NULL AND p.payment_code != '' THEN 'Manual'
            ELSE 'Chapa Payment'
        END AS payment_method
    FROM requests r
    JOIN paymentproof p ON r.id = p.request_id
    WHERE r.requested_by = ?
";

$params = array($user_id);
$types = "i";

// Add status filter
if ($status_filter !== 'all') {
    $query .= " AND p.verified_status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Add payment method filter
if ($payment_method_filter !== 'all') {
    if ($payment_method_filter === 'Manual') {
        $query .= " AND p.payment_code IS NOT NULL AND p.payment_code != ''";
    } elseif ($payment_method_filter === 'Chapa') {
        $query .= " AND (p.payment_code IS NULL OR p.payment_code = '')";
    }
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
if (count($params) > 1) {
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param($types, $params[0]);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History - UoG MRTS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
      <link rel="stylesheet" href="external_payment_history.css">
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
                <a href="external_dashboard.php">Dashboard</a> 
                <a href="external_user.php">Request Page</a>
                <a href="view_requests.php">View Requests</a>
                <a href="external_payment_upload.php">Upload Payment</a>
                <a href="external_payment_history.php" class="active">Payment History</a>
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
                <h1><i class="fas fa-history"></i> Payment History</h1>
                <p>View your payment transactions and status</p>
            </div>
            
            <!-- Status filter buttons -->
            <div class="filters" style= "  " >
                <button class="filter-btn <?= $status_filter === 'all' ? 'active' : '' ?>" data-filter="all" data-type="status">All Payments</button>
                <button class="filter-btn <?= $status_filter === 'Pending' ? 'active' : '' ?>" data-filter="Pending" data-type="status">Pending</button>
                <button class="filter-btn <?= $status_filter === 'Verified' ? 'active' : '' ?>" data-filter="Verified" data-type="status">Verified</button>
                <button class="filter-btn <?= $status_filter === 'Rejected' ? 'active' : '' ?>" data-filter="Rejected" data-type="status">Rejected</button>
                
               
            </div>

            <!-- Status filter buttons -->
            <div class="filters">
             <!-- Payment method filter buttons -->
                <button class="filter-btn <?= $payment_method_filter === 'all' ? 'active' : '' ?>" data-filter="all" data-type="payment_method">All Methods</button>
                <button class="filter-btn <?= $payment_method_filter === 'Manual' ? 'active' : '' ?>" data-filter="Manual" data-type="payment_method">Manual Payment</button>
                <button class="filter-btn <?= $payment_method_filter === 'Chapa' ? 'active' : '' ?>" data-filter="Chapa" data-type="payment_method">Chapa Payment</button>
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
                                <th>Amount (ETB)</th>
                                <th>Payment Method</th>
                                <th>Payment Code</th>
                                <th>Status</th>
                                <th>Screenshot</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): 
                                $status_class = 'status-' . strtolower($row['verified_status']);
                                $method_class = 'method-' . strtolower(str_replace(' ', '-', $row['payment_method']));
                            ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($row['id']) ?></td>
                                    <td>#<?= htmlspecialchars($row['request_id']) ?></td>
                                    <td><?= htmlspecialchars($row['issue_title']) ?></td>
                                    <td><strong><?= number_format($row['price'], 2) ?></strong></td>
                                    <td>
                                        <span class="method-badge <?= $method_class ?>">
                                            <?= htmlspecialchars($row['payment_method']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['payment_code'])): ?>
                                            <?= htmlspecialchars($row['payment_code']) ?>
                                        <?php else: ?>
                                            <span style="color: var(--muted);">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $status_class ?>">
                                            <?= htmlspecialchars($row['verified_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['payment_screenshot_path'])): ?>
                                            <a href="<?= htmlspecialchars($row['payment_screenshot_path']) ?>" target="_blank" class="view-link">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        <?php else: ?>
                                            <span style="color: var(--muted);">-</span>
                                        <?php endif; ?>
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
                    <h3>No payment history found</h3>
                    <p><?php echo ($status_filter !== 'all' || $payment_method_filter !== 'all') ? 'Try adjusting your filters' : 'You haven\'t made any payments yet'; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        
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