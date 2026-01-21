<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'external_user') {
    die("Unauthorized access.");
}

$conn = new mysqli("localhost", "root", "", "test_uog");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$request_id = $_GET['request_id'] ?? null;
$tx_ref     = $_GET['tx_ref'] ?? null;

if ($request_id) {

    // ✅ Update paymentproof
    if ($tx_ref) {
        $stmt = $conn->prepare("
            UPDATE paymentproof 
            SET verified_status = 'Verified', tx_ref = ?
            WHERE request_id = ?
        ");
        $stmt->bind_param("si", $tx_ref, $request_id);
    } else {
        $stmt = $conn->prepare("
            UPDATE paymentproof 
            SET verified_status = 'Verified'
            WHERE request_id = ?
        ");
        $stmt->bind_param("i", $request_id);
    }
    $stmt->execute();
    $stmt->close();

    // ✅ Fetch joined info (payment + request + user)
    $stmt = $conn->prepare("
        SELECT r.id AS request_id, r.price_status, r.issue_title,
               u.fullname, u.email,
               p.verified_status, p.tx_ref, p.price
        FROM requests r
        INNER JOIN users u ON r.requested_by = u.id
        LEFT JOIN paymentproof p ON r.id = p.request_id
        WHERE r.id = ?
    ");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $payment = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    if (!$payment) {
        die("❌ Payment record not found.");
    }
} else {
    echo "<p>❌ Invalid request. Missing Request ID.</p>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmation | UoG MRTS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
      <link rel="stylesheet" href="external_payment_success.css">
    <style>
      
    </style>
</head>
<body>
    <div class="container">
        <div class="success-card">
            <div class="success-icon">✅</div>
            <h1>Payment Completed Successfully!</h1>
            <p class="subtitle">Your payment has been processed and verified</p>
            
            <div class="payment-details">
                <div class="detail-row">
                    <span class="detail-label">Request Title:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($payment['issue_title'] ?? 'Maintenance Request'); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Request ID:</span>
                    <span class="detail-value">#<?php echo htmlspecialchars($payment['request_id']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Transaction Reference:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($payment['tx_ref'] ?? 'N/A'); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Full Name:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($payment['fullname']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($payment['email']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Amount Paid:</span>
                    <span class="detail-value amount"><?php echo htmlspecialchars($payment['price'] ?? $payment['price_status']); ?> ETB</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span class="status-badge"><?php echo htmlspecialchars($payment['verified_status']); ?></span>
                    </span>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href='generate_receipt.php?request_id=<?php echo urlencode($request_id); ?>' target='_blank' class="btn btn-primary">
                    📄 Download Receipt
                </a>
                <a href='view_requests.php' class="btn btn-secondary">
                    ← Back to My Requests
                </a>
            </div>
            
            <p class="support-note">
                Having issues? Contact support at support@uog-mrts.edu.et
            </p>
        </div>
    </div>
</body>
</html>