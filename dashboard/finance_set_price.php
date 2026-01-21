<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'finance') {
    die("Unauthorized access.");
}
require_once __DIR__ . '/notifications_functions.php';
require __DIR__ . '/email_functions.php'; // Adjust path

$conn = new mysqli("localhost", "root", "", "test_uog");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$request_id = $_POST['request_id'];
$price = $_POST['price'];
$instructions = $_POST['payment_instructions'];

// Check if a paymentproof record exists for this request
$check = $conn->prepare("SELECT id FROM paymentproof WHERE request_id = ?");
$check->bind_param("i", $request_id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    // Update existing record
    $sql = "UPDATE paymentproof 
            SET price = ?, payment_instructions = ?, verified_status = 'Waiting Payment'
            WHERE request_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dsi", $price, $instructions, $request_id);
} else {
    // Insert new record
    $sql = "INSERT INTO paymentproof (request_id, price, payment_instructions, verified_status, created_at)
            VALUES (?, ?, ?, 'Waiting Payment', NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ids", $request_id, $price, $instructions);
}

// Execute
if ($stmt->execute()) {
    // Update request status
    $update = $conn->prepare("UPDATE requests SET price_status = 'Pending Payment' WHERE id = ?");
    $update->bind_param("i", $request_id);
    $update->execute();

    // Fetch external user email
    $query = "
        SELECT u.email, u.fullname, r.issue_title 
        FROM requests r
        JOIN users u ON r.requested_by = u.id
        WHERE r.id = ?
    ";
    $stmt2 = $conn->prepare($query);
    $stmt2->bind_param("i", $request_id);
    $stmt2->execute();
    $result = $stmt2->get_result();
    if ($row = $result->fetch_assoc()) {
        $external_email = $row['email'];
        $external_name = $row['fullname'];
        $issue_title = $row['issue_title'];

        // Send email to external user
        notifyExternalPriceSet($external_email, $external_name, $issue_title, $price, $instructions);
        notifyExternalPriceSetApp($conn, $user_id, $request_id, $issue_title, $price);
    }

    header("Location: finance_pending_price.php?status=price_set");
    exit;
} else {
    echo "Error: " . $stmt->error;
}

$conn->close();
?>
