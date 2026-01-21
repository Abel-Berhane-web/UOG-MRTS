<?php
// === chieftech_reports.php ===
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'chief_technician') {
    die("Unauthorized access.");
}

require_once __DIR__ . '/notifications_functions.php';

$conn = new mysqli("localhost", "root", "", "test_uog");
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

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

// Handle CSV export
if (isset($_POST['export_csv'])) {
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $category = $_POST['category'] ?? '';
    $status_filter = $_POST['status_filter'] ?? '';

    // Basic validation
    $start_date_sql = $conn->real_escape_string($start_date);
    $end_date_sql = $conn->real_escape_string($end_date);
    $category_sql = $conn->real_escape_string($category);
    $status_sql = $conn->real_escape_string($status_filter);

    $where = "1=1";
    if ($start_date) $where .= " AND created_at >= '$start_date_sql 00:00:00'";
    if ($end_date) $where .= " AND created_at <= '$end_date_sql 23:59:59'";
    if ($category) $where .= " AND category = '$category_sql'";
    if ($status_filter && $status_filter !== 'all') $where .= " AND status = '$status_sql'";

    $sql = "SELECT r.id, r.issue_title, r.category, r.status, r.created_at, u.username AS requester, t.username AS technician
            FROM requests r
            LEFT JOIN users u ON r.requested_by = u.id
            LEFT JOIN users t ON r.assigned_technician_id = t.id
            WHERE $where
            ORDER BY r.created_at DESC";

    $result = $conn->query($sql);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="maintenance_requests_report.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Request ID', 'Title', 'Category', 'Status', 'Created At', 'Requester', 'Technician']);

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['issue_title'],
            $row['category'],
            $row['status'],
            $row['created_at'],
            $row['requester'],
            $row['technician'] ?? 'Unassigned'
        ]);
    }
    fclose($output);
    exit;
}

// Get filters from GET or POST (for display & query)
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$category = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? 'all';

// Build WHERE clause
$where = "1=1";
$params = [];
$types = '';

if ($start_date) {
    $where .= " AND r.created_at >= ?";
    $params[] = $start_date . " 00:00:00";
    $types .= 's';
}
if ($end_date) {
    $where .= " AND r.created_at <= ?";
    $params[] = $end_date . " 23:59:59";
    $types .= 's';
}
if ($category) {
    $where .= " AND r.category = ?";
    $params[] = $category;
    $types .= 's';
}
if ($status_filter && $status_filter !== 'all') {
    $where .= " AND r.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$sql = "SELECT r.id, r.issue_title, r.category, r.status, r.created_at, u.username AS requester, t.username AS technician
        FROM requests r
        LEFT JOIN users u ON r.requested_by = u.id
        LEFT JOIN users t ON r.assigned_technician_id = t.id
        WHERE $where
        ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get categories for filter dropdown
$cat_result = $conn->query("SELECT DISTINCT category FROM requests ORDER BY category ASC");
$categories = [];
while ($row = $cat_result->fetch_assoc()) {
    $categories[] = $row['category'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports - UoG MRTS Chief Technician</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="chieftech_reports.css"> 
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
           <a href="chief_technician_dashboard.php" >Dashboard</a> 
                <a href="chief_technician.php">Assign Technician</a>
                <a href="chieftech_reports.php" style="color: var(--green-apple); font-weight: bold;">Report</a>
                <a href="cheif_manage_technicians.php">Manage Technicians</a>
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
    <div class="page-header">
        <div>
            <p class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Chief Technician'); ?>!</p>
            <h1 class="page-title">Maintenance Requests Report</h1>
        </div>
    </div>
    
    <!-- Status filter buttons -->
    <div class="filters">
        <button class="filter-btn <?= $status_filter === 'all' ? 'active' : '' ?>" data-filter="all">All Requests</button>
        <button class="filter-btn <?= $status_filter === 'Pending Assignment' ? 'active' : '' ?>" data-filter="Pending Assignment">Pending Assignment</button>
        <button class="filter-btn <?= $status_filter === 'Assigned' ? 'active' : '' ?>" data-filter="Assigned">Assigned</button>
        <button class="filter-btn <?= $status_filter === 'In Progress' ? 'active' : '' ?>" data-filter="In Progress">In Progress</button>
        <button class="filter-btn <?= $status_filter === 'Completed' ? 'active' : '' ?>" data-filter="Completed">Completed</button>
    </div>
    
    <!-- Filter form -->
    <div class="filter-box">
        <h3 class="filter-title">Filter Reports</h3>
        <form method="get" action="chieftech_reports.php" class="filter-form" id="filterForm">
            <input type="hidden" name="status" id="statusFilter" value="<?= htmlspecialchars($status_filter) ?>">
            
            <div class="form-group">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-input" value="<?= htmlspecialchars($start_date) ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-input" value="<?= htmlspecialchars($end_date) ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Category</label>
                <select name="category" class="form-select">
                    <option value="">-- All Categories --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= $cat === $category ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <button type="button" class="btn btn-secondary" onclick="resetFilters()">Reset Filters</button>
            </div>
        </form>
        
        <!-- Export form -->
        <form method="post" action="chieftech_reports.php" class="export-form">
            <!-- Hidden fields to keep filters on export -->
            <input type="hidden" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
            <input type="hidden" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
            <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
            <input type="hidden" name="status_filter" value="<?= htmlspecialchars($status_filter) ?>">
            <button type="submit" name="export_csv" class="btn btn-success">📊 Export to CSV</button>
        </form>
    </div>
    
    <!-- Requests table -->
    <div style="overflow-x: auto;">
        <table class="requests-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Requester</th>
                    <th>Technician</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows === 0): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <h3>No requests found</h3>
                                <p><?php echo ($start_date || $end_date || $category || $status_filter !== 'all') ? 'Try adjusting your filters' : 'No maintenance requests in the system'; ?></p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['issue_title']) ?></td>
                            <td><?= htmlspecialchars($row['category']) ?></td>
                            <td>
                                <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $row['status'])) ?>">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            <td><span class="timestamp"><?= htmlspecialchars($row['created_at']) ?></span></td>
                            <td><?= htmlspecialchars($row['requester']) ?></td>
                            <td><?= htmlspecialchars($row['technician'] ?? 'Unassigned') ?></td>
                        </tr>
                    <?php endwhile; ?>
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
    
    // Status filter functionality
    document.querySelectorAll('.filter-btn').forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            document.getElementById('statusFilter').value = filter;
            document.getElementById('filterForm').submit();
        });
    });
    
    // Reset filters function
    function resetFilters() {
        document.getElementById('statusFilter').value = 'all';
        document.querySelectorAll('input[name="start_date"], input[name="end_date"]').forEach(input => {
            input.value = '';
        });
        document.querySelector('select[name="category"]').value = '';
        document.getElementById('filterForm').submit();
    }
</script>

</body>
</html>
<?php
$conn->close();
?>