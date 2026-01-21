<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'external_user') {
    die("Unauthorized access.");
}

require __DIR__ . '/email_functions.php'; // Adjust path

$conn = new mysqli("localhost", "root", "", "test_uog");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id   = $_SESSION['user_id'];
$title     = trim($_POST['issue_title']);
$desc      = trim($_POST['issue_description']);
$category  = $_POST['category'];
$campus    = $_POST['campus'];
$building  = $_POST['building_number'];
$room      = $_POST['room_number'];

// 🔹 Check for duplicate request by the same user
$dup_sql = "SELECT id FROM requests 
            WHERE requested_by = ? 
              AND issue_title = ? 
              AND issue_description = ? 
              AND category = ? 
              AND campus = ? 
              AND building_number = ? 
              AND room_number = ?";
$dup_stmt = $conn->prepare($dup_sql);
$dup_stmt->bind_param("issssss", $user_id, $title, $desc, $category, $campus, $building, $room);
$dup_stmt->execute();
$dup_result = $dup_stmt->get_result();

if ($dup_result->num_rows > 0) {
    // Duplicate found → prevent insertion
    header("Location: external_user.php?status=duplicate_request");
    exit;
}

// Upload helper function
function uploadFile($file, $folder) {
    if ($file && isset($file['error']) && $file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid("file_", true) . "." . $ext;
        $dir = __DIR__ . "/uploads/$folder";

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $path = $dir . "/" . $filename;

        if (move_uploaded_file($file['tmp_name'], $path)) {
            // Return relative path for DB
            return "uploads/$folder/" . $filename;
        } else {
            error_log("❌ Failed to move uploaded file: " . $file['name']);
        }
    } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
        error_log("⚠️ Upload error " . $file['error'] . " for " . $file['name']);
    }
    return null;
}


// Optional media uploads
$image_path = uploadFile($_FILES['image'], 'images');
$audio_path = uploadFile($_FILES['audio'], 'audio');
$video_path = uploadFile($_FILES['video'], 'videos');

// Insert request (Stage 1: Pending Price)
$sql = "INSERT INTO requests (
    requested_by, issue_title, issue_description, category, campus,
    building_number, room_number, image_path, audio_path, video_path,
    user_type, status, price_status
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'external', 'Pending Assignment', 'Not Set')";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "isssssssss",
    $user_id, $title, $desc, $category, $campus,
    $building, $room, $image_path, $audio_path, $video_path
);

if ($stmt->execute()) {
    $request_id = $stmt->insert_id;

    // Fetch user name for the email
    $res = $conn->query("SELECT fullname FROM users WHERE id = $user_id");
    $user_data = $res->fetch_assoc();
    $user_name = $user_data['fullname'];

    // Finance email
    $finance_email = "alemubela6@gmail.com"; 

    // Send email to finance
    notifyFinanceNewRequest($finance_email, $request_id, $title, $user_name);
      require_once __DIR__ . '/notifications_functions.php';

    // Assuming finance user_id = 2 (adjust to your finance user ID)
 notifyFinanceSetPrice($conn, $request_id, $title, $user_name);

    header("Location: external_user.php?status=request_submitted");
    exit;
} else {
    echo "Error: " . $stmt->error;
}

$conn->close();
?>
