<?php  
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

// ✅ Include email functions
require_once __DIR__ . '/email_functions.php';
require_once __DIR__ . '/notifications_functions.php';
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "test_uog";

// DB connection
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Logged-in user ID
$user_id = $_SESSION['user_id'];

// Fetch form data (sanitize a bit)
$title       = trim($_POST['issue_title']);
$description = trim($_POST['issue_description']);
$category    = $_POST['category'];
$campus      = $_POST['campus'];
$building    = $_POST['building_number'];
$room        = $_POST['room_number'];

// ✅ Prevent duplicate request (same user + same details + not completed)
$dup_sql = "SELECT id FROM requests 
            WHERE requested_by = ? 
              AND issue_title = ? 
              AND issue_description = ? 
              AND category = ? 
              AND campus = ? 
              AND building_number = ? 
              AND room_number = ? 
              AND status != 'Completed'
            LIMIT 1";
$dup_stmt = $conn->prepare($dup_sql);
$dup_stmt->bind_param("issssss", $user_id, $title, $description, $category, $campus, $building, $room);
$dup_stmt->execute();
$dup_result = $dup_stmt->get_result();

if ($dup_result->num_rows > 0) {
    header("Location: ../submit.php?status=duplicate");
    exit;
}

// Upload function
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


// Upload files
$image_path = uploadFile($_FILES['image'], 'images');
$audio_path = uploadFile($_FILES['audio'], 'audio');
$video_path = uploadFile($_FILES['video'], 'video');

// Insert the request into database
$sql = "INSERT INTO requests (
    requested_by, issue_title, issue_description, category,
    campus, building_number, room_number,
    image_path, audio_path, video_path, status
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending Assignment')";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "isssssssss",
    $user_id, $title, $description, $category,
    $campus, $building, $room,
    $image_path, $audio_path, $video_path
);

if ($stmt->execute()) {
    $request_id = $stmt->insert_id;

    // ✅ Load-balanced technician assignment
    $tech_sql = "
        SELECT u.id, COUNT(r.id) AS active_requests
        FROM users u
        LEFT JOIN requests r 
            ON u.id = r.assigned_technician_id 
           AND r.status IN ('Assigned', 'In Progress')
        WHERE u.role = 'technician' AND u.specialization = ?
        GROUP BY u.id
        ORDER BY active_requests ASC
        LIMIT 1
    ";

    $tech_stmt = $conn->prepare($tech_sql);
    $tech_stmt->bind_param("s", $category);
    $tech_stmt->execute();
    $tech_result = $tech_stmt->get_result();

    if ($tech = $tech_result->fetch_assoc()) {
        $tech_id = $tech['id'];
        $status = 'Assigned';

        $assign_sql = "UPDATE requests SET assigned_technician_id = ?, status = ? WHERE id = ?";
        $assign_stmt = $conn->prepare($assign_sql);
        $assign_stmt->bind_param("isi", $tech_id, $status, $request_id);
        $assign_stmt->execute();

        // ✅ Get technician email for notification
        $email_sql = "SELECT email FROM users WHERE id = ?";
        $email_stmt = $conn->prepare($email_sql);
        $email_stmt->bind_param("i", $tech_id);
        $email_stmt->execute();
        $email_result = $email_stmt->get_result();
        $email_row = $email_result->fetch_assoc();
        $technicianEmail = $email_row['email'];

        // ✅ Send technician assignment email
        notifyTechnicianAssigned($technicianEmail, $request_id, $title, $category, $campus, $room);
        notifyTechnicianAssignedApp($conn, $tech_id, $request_id, $issue_title, $campus, $room);

    } else {
        // No available technician: status = Pending Assignment
        $conn->query("UPDATE requests SET status = 'Pending Assignment' WHERE id = $request_id");
    }

    // Redirect to success
    header("Location: ../submit.php?status=success");
    exit;

} else {
    echo "Error: " . $stmt->error;
}

$conn->close();
?>
