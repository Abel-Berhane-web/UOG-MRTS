<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'external_user') {
    die("Unauthorized access.");
}

$conn = new mysqli("localhost", "root", "", "test_uog");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$chapa_secret = "CHASECK_TEST-Gm8TiqQhsjtrxbNnVvUbNp2psghX0IFH";

$request_id = $_POST['request_id'] ?? null;
$amount     = $_POST['amount'] ?? null;
$email      = $_POST['email'] ?? null;

if (!$request_id || !$amount || !$email) {
    die("❌ Invalid request, missing required parameters.");
}

$first_name = $_SESSION['fullname'] ?? 'External';
$last_name  = ''; // optional

// Generate unique transaction reference
$tx_ref = 'tx_' . uniqid();

// ✅ Only update existing payment record
$stmt = $conn->prepare("
    UPDATE paymentproof 
    SET tx_ref = ?, price = ?, verified_status = 'Waiting Payment'
    WHERE request_id = ?
");
$stmt->bind_param("sdi", $tx_ref, $amount, $request_id);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    die("❌ Payment record does not exist for this request. Cannot pay.");
}
$stmt->close();


// Chapa return URL
$base_url = 'http://localhost/uog/dashboard';
$return_url = $base_url . "/external_payment_success.php?request_id=$request_id";

// Prepare payment data
$custom_title = "Request" . $request_id;
if (strlen($custom_title) > 16) {
    $custom_title = substr($custom_title, 0, 16);
}

$data = [
    'amount'   => $amount,
    'currency' => 'ETB',
    'email'    => $email,
    'first_name' => $first_name,
    'last_name'  => $last_name,
    'tx_ref'     => $tx_ref,
    'return_url' => $return_url,
    'customization' => [
        'title'       => $custom_title,
        'description' => 'Payment for your maintenance request'
    ]
];

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.chapa.co/v1/transaction/initialize");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $chapa_secret",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
if ($response === false) die("cURL Error: " . curl_error($ch));
curl_close($ch);

$result = json_decode($response, true);

// Redirect to Chapa checkout if success
if (isset($result['status']) && $result['status'] === 'success') {
    header("Location: " . $result['data']['checkout_url']);
    exit;
} else {
    echo "<h3>❌ Payment initialization failed!</h3>";
    echo "<pre>"; print_r($result); echo "</pre>";
}
?>
