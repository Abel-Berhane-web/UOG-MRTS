<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'finance') {
    die("Unauthorized access.");
}
require_once __DIR__ . '/notifications_functions.php';
require __DIR__ . '/email_functions.php'; // include email functions

$conn = new mysqli("localhost", "root", "", "test_uog");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Handle verification form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_id'])) {
    $payment_id = intval($_POST['payment_id']);
    $request_id = intval($_POST['request_id']);

    // Update payment proof status
    $stmt = $conn->prepare("UPDATE paymentproof SET verified_status = 'Verified' WHERE id = ?");
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();

    // Update request status to Pending Assignment
    $stmt2 = $conn->prepare("UPDATE requests SET status = 'Pending Assignment', price_status = 'Verified' WHERE id = ?");
    $stmt2->bind_param("i", $request_id);
    $stmt2->execute();

    // Fetch external user info and request title
    $stmt3 = $conn->prepare("SELECT u.email, u.id as user_id, r.issue_title FROM requests r JOIN users u ON r.requested_by = u.id WHERE r.id = ?");
    $stmt3->bind_param("i", $request_id);
    $stmt3->execute();
    $result = $stmt3->get_result();
    if ($row = $result->fetch_assoc()) {
        $user_email = $row['email'];
        $user_id = $row['user_id'];
        $issue_title = $row['issue_title'];
        notifyExternalPaymentVerified($user_email, $request_id, $issue_title); // send email
        notifyExternalPaymentVerifiedApp($conn, $user_id, $request_id, $issue_title); // send app notification
    }

    header("Location: finance_payment_verification.php?status=verified");
    exit;
}

// Fetch requests that are waiting for payment verification
$sql = "
SELECT r.id AS request_id, r.issue_title, r.category, r.campus, r.room_number,
       u.fullname, u.email, u.phone, u.telegram,
       p.id AS payment_id, p.payment_code, p.payment_screenshot_path
FROM requests r
JOIN paymentproof p ON r.id = p.request_id
JOIN users u ON r.requested_by = u.id
WHERE p.verified_status = 'Pending Payment Verification'
  AND r.price_status = 'Paid'
ORDER BY r.created_at DESC
";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Verification | UoG MRTS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
      <link rel="stylesheet" href="finance_payment_verification.css">   
    <style>
        
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Payment Verification</h1>
            <p class="subtitle">Verify payment proofs from external users</p>
        </header>
        
        <div class="welcome-text">
            Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>! 
            You are logged in as <strong>Finance</strong>.
        </div>
        
        <?php if (isset($_GET['status']) && $_GET['status'] === 'verified'): ?>
            <div class="alert alert-success">
                ✅ Payment verified successfully! Notification sent to the user.
            </div>
        <?php endif; ?>
        
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-label">Pending Verifications</div>
                <div class="stat-number"><?php echo $result->num_rows; ?></div>
                <div class="stat-label">Awaiting Your Review</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Your Role</div>
                <div class="stat-number"><?php echo $_SESSION['username'] ?? 'User'; ?></div>
                <div class="stat-label">Payment Verification</div>
            </div>
        </div>
        
        <h2>Payments Pending Verification</h2>
        
        <?php if ($result->num_rows > 0): ?>
            <table class="payments-table">
                <thead>
                    <tr>
                        <th>Request Details</th>
                        <th>Requester Information</th>
                        <th>Payment Code</th>
                        <th>Screenshot</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div class="request-title"><?= htmlspecialchars($row['issue_title']) ?></div>
                            <div class="request-category"><?= htmlspecialchars($row['category']) ?></div>
                            <div style="margin-top: 10px;">
                                <div><?= htmlspecialchars($row['campus']) ?></div>
                                <div>Room <?= htmlspecialchars($row['room_number']) ?></div>
                            </div>
                        </td>
                        <td>
                            <div class="requester-info">
                                <div class="requester-name"><?= htmlspecialchars($row['fullname']) ?></div>
                                <div class="contact-info">
                                    <div>📧 <?= htmlspecialchars($row['email']) ?></div>
                                    <div>📞 <?= htmlspecialchars($row['phone']) ?></div>
                                    <div>💬 <?= htmlspecialchars($row['telegram']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="payment-code"><?= htmlspecialchars($row['payment_code']) ?></div>
                        </td>
                        <td>
                            <?php if (!empty($row['payment_screenshot_path'])): ?>
                                <a href="<?= htmlspecialchars($row['payment_screenshot_path']) ?>" 
                                   target="_blank" class="screenshot-link">
                                   View Screenshot
                                </a>
                            <?php else: ?>
                                <em>No file uploaded</em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" class="verify-form">
                                <input type="hidden" name="payment_id" value="<?= $row['payment_id'] ?>">
                                <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                                <button type="submit" class="verify-btn" 
                                        onclick="return confirm('Are you sure you want to verify this payment? This will notify the user and move the request to Pending Assignment.');">
                                    ✅ Verify Payment
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-payments">
                <h3>No payments pending verification</h3>
                <p>All payments have been verified. Check back later for new submissions.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Add confirmation before verifying payments
        document.querySelectorAll('.verify-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!confirm('Are you sure you want to verify this payment? This action cannot be undone.')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>