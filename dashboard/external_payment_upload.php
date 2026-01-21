<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'external_user') {
    die("Unauthorized access.");
}

require_once __DIR__ . '/notifications_functions.php';

$conn = new mysqli("localhost", "root", "", "test_uog");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Get unread count
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

$user_id = $_SESSION['user_id'];

// Fetch requests that are waiting payment
$query = "
    SELECT r.id AS request_id, r.issue_title, p.price, p.payment_instructions
    FROM requests r
    JOIN paymentproof p ON r.id = p.request_id    
    WHERE r.requested_by = ? 
      AND p.verified_status = 'Waiting Payment' 
      AND r.price_status = 'Pending Payment'
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Chapa Test Secret Key
$chapa_secret = "CHASECK_TEST-Gm8TiqQhsjtrxbNnVvUbNp2psghX0IFH";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Payment Proof / Pay with Chapa - UoG MRTS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
      <link rel="stylesheet" href="external_payment_upload.css">
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
            <a href="external_payment_history.php">Payment History</a>
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
            <div class="header">
                <h1><i class="fas fa-credit-card"></i> Payment Requests</h1>
                <p>Complete payment for your maintenance requests</p>
            </div>
            
            <?php if ($result->num_rows > 0): ?>
                <div class="requests-grid">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="request-card">
                            <div class="status-badge">Payment Required</div>
                            
                            <div class="card-header">
                                <div>
                                    <h3 class="request-title"><?= htmlspecialchars($row['issue_title']) ?></h3>
                                    <span class="request-id">ID: #<?= $row['request_id'] ?></span>
                                </div>
                            </div>
                            
                            <div class="price-tag">
                                <?= number_format($row['price'], 2) ?> ETB
                            </div>
                            
                            <div class="instructions">
                                <h4><i class="fas fa-info-circle"></i> Payment Instructions</h4>
                                <p><?= htmlspecialchars($row['payment_instructions']) ?></p>
                            </div>
                            
                            <div class="payment-options">
                                <!-- Manual Upload Form -->
                                <div class="option-card">
                                    <div class="option-header">
                                        <i class="fas fa-upload option-icon"></i>
                                        <h4>Manual Payment Proof</h4>
                                    </div>
                                    <form method="POST" enctype="multipart/form-data" action="external_payment_upload_handler.php">
                                        <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                                        
                                        <div class="form-group">
                                            <label for="payment_code_<?= $row['request_id'] ?>">Transaction/Payment Code</label>
                                            <input type="text" id="payment_code_<?= $row['request_id'] ?>" name="payment_code" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="payment_screenshot_<?= $row['request_id'] ?>">Upload Payment Screenshot</label>
                                            <input type="file" id="payment_screenshot_<?= $row['request_id'] ?>" name="payment_screenshot" accept="image/*" required>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i> Submit Payment Proof
                                        </button>
                                    </form>
                                </div>
                                
                                <!-- Chapa Payment Form -->
                                <div class="option-card">
                                    <div class="option-header">
                                        <i class="fas fa-bolt option-icon"></i>
                                        <h4>Instant Payment</h4>
                                    </div>
                                    <form method="POST" action="external_pay_with_chapa.php">
                                        <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                                        <input type="hidden" name="amount" value="<?= $row['price'] ?>">
                                        <input type="hidden" name="email" value="<?= $_SESSION['email'] ?>">
                                        
                                        <button type="submit" class="btn btn-chapa">
                                            <i class="fas fa-wallet"></i> Pay with Chapa
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-requests">
                    <i class="fas fa-check-circle"></i>
                    <h2>No pending payments</h2>
                    <p>You don't have any requests waiting for payment at the moment.</p>
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
        // Mobile menu toggle
        const menuBtn = document.getElementById('menuBtn');
        const navLinks = document.querySelector('.nav-links');
        
        if (menuBtn && navLinks) {
            menuBtn.addEventListener('click', () => {
                const isVisible = navLinks.style.display === 'flex';
                navLinks.style.display = isVisible ? 'none' : 'flex';
            });
        }
        
        // Add some interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.request-card');
            
            cards.forEach(card => {
                card.addEventListener('mouseenter', () => {
                    card.style.zIndex = '10';
                });
                
                card.addEventListener('mouseleave', () => {
                    card.style.zIndex = '1';
                });
            });
            
            // File input styling
            const fileInputs = document.querySelectorAll('input[type="file"]');
            fileInputs.forEach(input => {
                input.addEventListener('change', function() {
                    if (this.files.length > 0) {
                        this.style.borderColor = 'var(--green-apple)';
                    }
                });
            });
            
            // Dynamic year for footer
            document.getElementById('year').textContent = new Date().getFullYear();
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>