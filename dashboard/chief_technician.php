<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'chief_technician') {
    die("Unauthorized access.");
}

// Include email functions file
require_once __DIR__ . '/email_functions.php';
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

// Handle assignment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['technician_id'])) {
    $request_id = intval($_POST['request_id']);
    $technician_id = intval($_POST['technician_id']);

    $assign_sql = "UPDATE requests SET assigned_technician_id = ?, status = 'Assigned' WHERE id = ?";
    $assign_stmt = $conn->prepare($assign_sql);
    $assign_stmt->bind_param("ii", $technician_id, $request_id);
    $assign_stmt->execute();

    // Fetch technician email
    $email_sql = "SELECT email FROM users WHERE id = ?";
    $email_stmt = $conn->prepare($email_sql);
    $email_stmt->bind_param("i", $technician_id);
    $email_stmt->execute();
    $email_result = $email_stmt->get_result();
    $technicianEmail = $email_result->fetch_assoc()['email'] ?? null;

    // Fetch request details
    $request_sql = "SELECT issue_title, category, campus, room_number FROM requests WHERE id = ?";
    $request_stmt = $conn->prepare($request_sql);
    $request_stmt->bind_param("i", $request_id);
    $request_stmt->execute();
    $request_result = $request_stmt->get_result();
    $request_details = $request_result->fetch_assoc();

    // Notify technician
    if ($technicianEmail && $request_details) {
        notifyTechnicianAssigned(
            $technicianEmail,
            $request_id,
            $request_details['issue_title'],
            $request_details['category'],
            $request_details['campus'],
            $request_details['room_number']
        );
    }
    
    // Redirect to avoid form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch requests: internal + external (external only if verified)
$sql = "
SELECT 
    r.*, 
    u.fullname AS requester_name, u.phone, u.telegram,
    t.fullname AS technician_name
FROM requests r
JOIN users u ON r.requested_by = u.id
LEFT JOIN users t ON r.assigned_technician_id = t.id
LEFT JOIN paymentproof p ON r.id = p.request_id
WHERE (r.user_type = 'internal' OR (r.user_type = 'external'  AND r.status = 'Pending Assignment' AND p.verified_status = 'Verified'))
ORDER BY r.created_at DESC
";

$result = $conn->query($sql);

// Fetch all technicians for dropdown
$tech_sql = "SELECT id, fullname FROM users WHERE role = 'technician'";
$tech_result = $conn->query($tech_sql);
$technicians = [];
while ($row = $tech_result->fetch_assoc()) {
    $technicians[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chief Technician Dashboard - UoG MRTS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="chief_technician.css"> 
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
                <a href="chief_technician_dashboard.php" >Dashboard</a> 
                <a href="chief_technician.php" style="color: var(--green-apple); font-weight: bold;">Assign Technician</a>
                <a href="chieftech_reports.php">Report</a>
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
        <div class="container">
            <div class="dashboard-header">
                <h1><i class="fas fa-tools"></i> Chief Technician Dashboard</h1>
                <p>Assign maintenance requests to technicians</p>
            </div>
            
            <p class="welcome-text">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</p>
            
            <?php if ($result->num_rows > 0): ?>
                <div class="requests-table-container">
                    <table class="requests-table">
                        <thead>
                            <tr>
                                <th>Issue Title</th>
                                <th>Category</th>
                                <th>Campus</th>
                                <th>Room</th>
                                <th>Status</th>
                                <th>Requester Info</th>
                                <th>Assigned Technician</th>
                                <th>Assign To</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): 
                                $status_class = 'status-' . strtolower(str_replace(' ', '-', $row['status']));
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($row['issue_title']) ?></td>
                                <td><?= htmlspecialchars($row['category']) ?></td>
                                <td><?= htmlspecialchars($row['campus']) ?></td>
                                <td><?= htmlspecialchars($row['room_number']) ?></td>
                                <td>
                                    <span class="status-badge <?= $status_class ?>">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="requester-info">
                                        <div><strong><?= htmlspecialchars($row['requester_name']) ?></strong></div>
                                        <div><i class="fas fa-phone"></i> <?= htmlspecialchars($row['phone']) ?></div>
                                        <div><i class="fab fa-telegram"></i> <?= htmlspecialchars($row['telegram']) ?></div>
                                    </div>
                                </td>
                                <td>
                                    <?= $row['technician_name'] ? htmlspecialchars($row['technician_name']) : "<i>Unassigned</i>" ?>
                                </td>
                                <td>
                                    <form method="POST" class="assign-form">
                                        <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                                        <select name="technician_id" class="form-select" required>
                                            <option value="">-- Select Technician --</option>
                                            <?php
                                            // Fetch technicians that match the request's category
                                            $cat = $row['category'];
                                            $cat_sql = "SELECT id, fullname FROM users 
                                                        WHERE role = 'technician' AND specialization = ?";
                                            $cat_stmt = $conn->prepare($cat_sql);
                                            $cat_stmt->bind_param("s", $cat);
                                            $cat_stmt->execute();
                                            $cat_result = $cat_stmt->get_result();

                                            while ($tech = $cat_result->fetch_assoc()): ?>
                                                <option value="<?= $tech['id'] ?>"><?= htmlspecialchars($tech['fullname']) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                </td>
                                <td>
                                        <button type="submit" class="btn btn-primary">Assign</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>No requests found</h3>
                    <p>There are currently no maintenance requests to display.</p>
                </div>
            <?php endif; ?>
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
                const isVisible = navLinks.style.display === 'flex';
                navLinks.style.display = isVisible ? 'none' : 'flex';
            });
        }
        
        // Add animations to table rows
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.requests-table tbody tr');
            
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