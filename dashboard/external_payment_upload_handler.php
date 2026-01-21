<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'external_user') {
    die("Unauthorized access.");
}
require_once __DIR__ . '/notifications_functions.php';
require __DIR__ . '/email_functions.php'; // Adjust path if needed

$conn = new mysqli("localhost", "root", "", "test_uog");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$request_id = $_POST['request_id'];
$payment_code = $_POST['payment_code'];

// Upload screenshot
function uploadFile($file, $folder) {
    if ($file['error'] === 0) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . "." . $ext;
        $path = "uploads/$folder/" . $filename;
        move_uploaded_file($file['tmp_name'], $path);
        return $path;
    }
    return null;
}

$payment_screenshot_path = uploadFile($_FILES['payment_screenshot'], 'payments');

if (!$payment_screenshot_path) {
    die("Failed to upload screenshot.");
}

// Update existing paymentproof entry
$sql = "UPDATE paymentproof 
        SET payment_code = ?, payment_screenshot_path = ?, verified_status = 'Pending Payment Verification', created_at = NOW()
        WHERE request_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $payment_code, $payment_screenshot_path, $request_id);

if ($stmt->execute()) {
    // Update request status
    $update = $conn->prepare("UPDATE requests SET price_status = 'Paid' WHERE id = ?");
    $update->bind_param("i", $request_id);
    $update->execute();

    // Fetch finance email and request details
    $query = "
SELECT u.fullname AS external_name, r.issue_title, f.email AS finance_email
FROM requests r
JOIN users u ON r.requested_by = u.id
JOIN users f ON f.role = 'finance'
WHERE r.id = ?
LIMIT 1
";

    $stmt2 = $conn->prepare($query);
    $stmt2->bind_param("i", $request_id);
    $stmt2->execute();
    $result = $stmt2->get_result();
    if ($row = $result->fetch_assoc()) {
        $finance_email = $row['finance_email'];
        $external_name = $row['external_name'];
        $issue_title = $row['issue_title'];

        // Send notification to finance
        notifyFinancePaymentUploaded($finance_email, $request_id, $issue_title, $external_name);
        notifyFinancePaymentUploadedApp($conn, $request_id, $issue_title, $user_name);
    }

    header("Location: external_payment_upload.php?status=submitted");
    exit;
} else {
    echo "Error: " . $stmt->error;
}

$conn->close();
?>
