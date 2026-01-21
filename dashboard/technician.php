<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technician') {
    header("Location: ../login.php");
    exit;
}

// ✅ Include email functions
require_once __DIR__ . '/email_functions.php';
require_once __DIR__ . '/notifications_functions.php';

$technician_id = $_SESSION['user_id'];

$conn = new mysqli("localhost", "root", "", "test_uog");
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
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

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['new_status'])) {
    $request_id = $_POST['request_id'];
    $new_status = $_POST['new_status'];

    $update_sql = "UPDATE requests SET status = ? WHERE id = ? AND assigned_technician_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("sii", $new_status, $request_id, $technician_id);
    $update_stmt->execute();

    // ✅ Only notify when status is Completed
    if ($new_status === 'Completed') {
        // Get requester id, email and issue title from the request
        $email_sql = "SELECT u.id AS user_id, u.email, r.issue_title 
                      FROM users u
                      JOIN requests r ON u.id = r.requested_by
                      WHERE r.id = ?";
        $email_stmt = $conn->prepare($email_sql);
        $email_stmt->bind_param("i", $request_id);
        $email_stmt->execute();
        $email_result = $email_stmt->get_result();

        if ($email_row = $email_result->fetch_assoc()) {
            $requesterId    = $email_row['user_id'];   // ✅ requester id
            $requesterEmail = $email_row['email'];
            $issue_title    = $email_row['issue_title'];

            // ✅ Send email notification
            notifyRequesterCompleted($requesterEmail, $request_id, $issue_title);

            // ✅ Send in-app notification
            notifyRequesterCompletedApp($conn, $requesterId, $request_id, $issue_title);
        }
    }
}

// Get assigned requests
$sql = "SELECT id, issue_title, category, campus, room_number, status FROM requests WHERE assigned_technician_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $technician_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Dashboard - UoG MRTS</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="technician.css"> 
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
                <a href="technician_dashboard.php">Dashboard</a>
                <a href="technician.php">My Requests</a>
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
                <h1><i class="fas fa-tools"></i> Technician Dashboard</h1>
                <p>Manage your assigned maintenance requests</p>
            </div>
            
            <?php if ($result->num_rows > 0): ?>
                <div class="requests-grid">
                    <?php while ($row = $result->fetch_assoc()): 
                        $status_class = 'status-' . strtolower(str_replace(' ', '-', $row['status']));
                    ?>
                        <div class="request-card">
                            <div class="card-header">
                                <div>
                                    <h3 class="request-title"><?= htmlspecialchars($row['issue_title']) ?></h3>
                                    <span class="request-id">ID: #<?= $row['id'] ?></span>
                                </div>
                                <span class="status-badge <?= $status_class ?>"><?= htmlspecialchars($row['status']) ?></span>
                            </div>
                            
                            <div class="request-details">
                                <div class="detail-item">
                                    <span class="detail-label">Category</span>
                                    <span class="detail-value"><?= htmlspecialchars($row['category']) ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Campus</span>
                                    <span class="detail-value"><?= htmlspecialchars($row['campus']) ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Room</span>
                                    <span class="detail-value"><?= htmlspecialchars($row['room_number']) ?></span>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <a href="request_detail.php?id=<?= $row['id'] ?>" class="view-btn">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                                
                                <form method="POST" class="action-form">
                                    <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                                    
                                    <?php if ($row['status'] === 'Assigned'): ?>
                                        <input type="hidden" name="new_status" value="In Progress">
                                        <button type="submit" class="action-btn btn-start">
                                            <i class="fas fa-play"></i> Start
                                        </button>
                                    <?php elseif ($row['status'] === 'In Progress'): ?>
                                        <input type="hidden" name="new_status" value="Completed">
                                        <button type="submit" class="action-btn btn-complete">
                                            <i class="fas fa-check"></i> Complete
                                        </button>
                                    <?php else: ?>
                                        <span class="btn-done">
                                            <i class="fas fa-check-circle"></i> Done
                                        </span>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-requests">
                    <i class="fas fa-clipboard-list"></i>
                    <h2>No assigned requests</h2>
                    <p>You don't have any maintenance requests assigned to you at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
      
            <div class="credits">© <span id="year"></span> UoG MRTS. All rights reserved.</div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        const menuBtn = document.getElementById('menuBtn');
        const navLinks = document.querySelector('.nav-links');
        
        if (menuBtn && navLinks) {
            menuBtn.addEventListener('click', () => {
                const isVisible = navLinks.style.display === 'flex';
                navLinks.style.display = isVisible ? 'none' : 'flex';
            });
        }
        
        // Dynamic year for footer
        document.getElementById('year').textContent = new Date().getFullYear();
        
        // Add animations to cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.request-card');
            
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>