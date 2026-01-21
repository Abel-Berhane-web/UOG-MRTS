<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'chief_technician') {
    die("Unauthorized access.");
}
require_once __DIR__ . '/notifications_functions.php';

// DB Connection
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
// --- Handle enable/disable via POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    $tech_id = intval($_POST['toggle_id']);

    // Fetch current status from DB
    $stmt = $conn->prepare("SELECT account_status FROM users WHERE id=? AND role='technician'");
    $stmt->bind_param("i", $tech_id);
    $stmt->execute();
    $stmt->bind_result($current_status);
    $stmt->fetch();
    $stmt->close();

    if ($current_status) {
        $new_status = ($current_status === 'enabled') ? 'disabled' : 'enabled';

        $stmt = $conn->prepare("UPDATE users SET account_status=? WHERE id=? AND role='technician'");
        $stmt->bind_param("si", $new_status, $tech_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: cheif_manage_technicians.php?status=updated");
    exit;
}

// --- Handle search ---
$search_query = "";
if (isset($_GET['search'])) {
    $search_query = $conn->real_escape_string($_GET['search']);
    $sql = "SELECT * FROM users 
            WHERE role='technician' 
              AND (username LIKE '%$search_query%' OR email LIKE '%$search_query%' OR fullname LIKE '%$search_query%')
            ORDER BY id ASC";
} else {
    $sql = "SELECT * FROM users WHERE role='technician' ORDER BY id ASC";
}

$result = $conn->query($sql);
$total_technicians = $result->num_rows;

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN account_status = 'enabled' THEN 1 ELSE 0 END) as enabled_count,
    SUM(CASE WHEN account_status = 'disabled' THEN 1 ELSE 0 END) as disabled_count
    FROM users WHERE role='technician'";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Technicians | UoG MRTS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link rel="stylesheet" href="cheif_manage_technicians.css"> 
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
                <a href="chieftech_reports.php">Report</a>
                <a href="cheif_manage_technicians.php" style="color: var(--green-apple); font-weight: bold;">Manage Technicians</a>
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
</nav>  <div class="container" style= " margin-top: 20px " >
        <div class="header">
            <h1><i class="fas fa-users-cog"></i> Manage Technicians</h1>
            <p>Oversee and manage all technical staff members</p>
        </div>
        
        <div class="welcome-text">
            Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>! 
            You are logged in as <strong>Chief Technician</strong>.
        </div>
        
        <?php if (isset($_GET['status'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Technician status updated successfully.
            </div>
        <?php endif; ?>
        
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-label">Total Technicians</div>
                <div class="stat-number"><?= $stats['total'] ?></div>
                <div class="stat-label">Registered Staff</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Active Technicians</div>
                <div class="stat-number"><?= $stats['enabled_count'] ?></div>
                <div class="stat-label">Available for assignments</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Inactive Technicians</div>
                <div class="stat-number"><?= $stats['disabled_count'] ?></div>
                <div class="stat-label">Currently disabled</div>
            </div>
        </div>
        
        <div class="search-section">
            <form method="GET" class="search-form">
                <input type="text" name="search" class="search-input" 
                       placeholder="Search by name, username, or email..." 
                       value="<?= htmlspecialchars($search_query) ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <a href="cheif_manage_technicians.php" class="btn btn-secondary">
                    <i class="fas fa-sync"></i> Reset
                </a>
            </form>
        </div>
        
        <?php if ($result->num_rows > 0): ?>
            <div style="overflow-x: auto;">
                <table class="technicians-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                           
                            <th>Specialization</th>
                            <th>Assigned</th>
                            <th>In Progress</th>
                            <th>Completed</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($tech = $result->fetch_assoc()): 
                            $tech_id = $tech['id'];

                            // Default counts
                            $counts = [
                                'assigned' => 0,
                                'in_progress' => 0,
                                'completed' => 0
                            ];

                            // Fetch counts grouped by status
                            $q = $conn->prepare("SELECT status, COUNT(*) AS cnt 
                                                FROM requests 
                                                WHERE assigned_technician_id=? 
                                                GROUP BY status");
                            $q->bind_param("i", $tech_id);
                            $q->execute();
                            $res = $q->get_result();
                            while ($row = $res->fetch_assoc()) {
                                $status = strtolower($row['status']); // normalize
                                if (isset($counts[$status])) {
                                    $counts[$status] = $row['cnt'];
                                }
                            }
                            $q->close();
                        ?>
                            <tr>
                                <td><?= $tech['id'] ?></td>
                                <td><?= htmlspecialchars($tech['fullname']) ?></td>
                                <td><?= htmlspecialchars($tech['email']) ?></td>
                                
                                <td><?= htmlspecialchars($tech['specialization'] ?? 'N/A') ?></td>
                                <td><span class="count-badge"><?= $counts['assigned'] ?></span></td>
                                <td><span class="count-badge"><?= $counts['in_progress'] ?></span></td>
                                <td><span class="count-badge"><?= $counts['completed'] ?></span></td>
                                <td>
                                    <span class="status-badge <?= $tech['account_status'] == 'enabled' ? 'status-enabled' : 'status-disabled' ?>">
                                        <?= ucfirst($tech['account_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="view_technician.php?id=<?= $tech['id'] ?>" class="btn btn-action btn-view">
                                            <i class="fas fa-user"></i> Profile
                                        </a>
                                        <a href="view_technician_requests.php?id=<?= $tech['id'] ?>" class="btn btn-action btn-req">
                                            <i class="fas fa-tasks"></i> Requests
                                        </a>

                                        <!-- Enable/Disable form -->
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="toggle_id" value="<?= $tech['id'] ?>">
                                            <button type="submit" class="btn btn-action btn-toggle <?= $tech['account_status'] == 'disabled' ? 'enabled' : '' ?>">
                                                <?php if ($tech['account_status'] == 'enabled'): ?>
                                                    <i class="fas fa-toggle-on"></i> Disable
                                                <?php else: ?>
                                                    <i class="fas fa-toggle-off"></i> Enable
                                                <?php endif; ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-technicians">
                <h3><i class="fas fa-user-slash"></i> No Technicians Found</h3>
                <p>No technician accounts match your search criteria.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Add confirmation before toggling technician status
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const button = this.querySelector('button');
                const action = button.textContent.includes('Enable') ? 'enable' : 'disable';
                
                if (!confirm(`Are you sure you want to ${action} this technician?`)) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>