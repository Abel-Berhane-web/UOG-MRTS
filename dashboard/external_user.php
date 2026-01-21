<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'external_user') {
    header("Location: ../login.php");
    exit;
}
require_once __DIR__ . '/notifications_functions.php';

$conn = new mysqli("localhost", "root", "", "test_uog");
if ($conn->connect_error) die("DB connection failed: " . $conn->connect_error);

// Get unread count only
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Submit Maintenance Request - UoG MRTS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
      <link rel="stylesheet" href="external_user.css">
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
    <h2>Submit Maintenance Request</h2>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'request_submitted'): ?>
        <div class="success-message">
            ✅ Request submitted successfully!
        </div>
        <script>
            // Auto clear form after success
            window.onload = function() {
                const form = document.querySelector("form");
                if (form) form.reset();
            };
        </script>
    <?php elseif (isset($_GET['status']) && $_GET['status'] === 'duplicate_request'): ?>
        <div class="error-message">
            ⚠️ This request is already registered. Please wait until it is solved or submit a different one.
        </div>
    <?php endif; ?>

    <form id="requestForm" action="submit_external_handler.php" method="POST" enctype="multipart/form-data">
        <div>
            <label for="issue_title">Issue Title:</label>
            <input type="text" id="issue_title" name="issue_title" required>
        </div>
        
        <div>
            <label for="issue_description">Issue Description:</label>
            <textarea id="issue_description" name="issue_description" required></textarea>
        </div>
        
        <div>
            <label for="category">Category:</label>
            <select id="category" name="category" required style="color: #ffffffff; background: #0f2016; textweight: bolder;">
                <option value=""> Select Category </option>
                <option value="Electrical">Electrical</option>
                <option value="Networking">Networking</option>
                <option value="Electronics">Electronics</option>
            </select>
        </div>
        
        <div>
            <label for="campus">Campus:</label>
            <select id="campus" name="campus" required style="color: #ffffffff; background: #0f2016; textweight: bolder; ">
                <option value="" > Select Campus </option>
                <option value="Fasil">Fasil</option>
                <option value="Tedy">Tedy</option>
                <option value="Maraki">Maraki</option>
                <option value="Teda">Teda</option>
                <option value="Hospital">Hospital</option>
            </select>
        </div>
        
        <div>
            <label for="building_number">Building Number:</label>
            <input type="text" id="building_number" name="building_number" required>
        </div>
        
        <div>
            <label for="room_number">Room Number:</label>
            <input type="text" id="room_number" name="room_number" required>
        </div>
        
        <!-- Optional File Uploads -->
       
<div class="file-upload-section">
    <h3 style="color: var(--muted); margin-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 8px;">Attach Files (Optional)</h3>
    
    <div class="file-upload-group">
        <label for="image" class="file-upload-label">
            
            <div class="file-upload-text" style="display: flex; margin-bottom: 10px">
                <span class="file-type">🖼️ Upload Image</span>
                <span class="file-info"  style="display: flex; margin-bottom: 10px">JPG, PNG, GIF (Max 5MB)</span >
            </div>
            <input type="file" id="image" name="image" accept="image/*" class="file-input">
        </label>
        <div class="file-preview" id="image-preview"></div>
    </div>
    
    <div class="file-upload-group">
        <label for="audio" class="file-upload-label">
            <div class="file-upload-text" style="display: flex; margin-bottom: 10px">
                <span class="file-type">🎵 Upload Audio</span>
                <span class="file-info"  style="display: flex; margin-bottom: 10px">MP3, WAV (Max 10MB)</span>
            </div>
            <input type="file" id="audio" name="audio" accept="audio/*" class="file-input">
        </label>
        <div class="file-preview" id="audio-preview"  style="display: flex; margin-bottom: 10px"></div>
    </div>
    
    <div class="file-upload-group">
        <label for="video" class="file-upload-label">
            
            <div class="file-upload-text"  style="display: flex; margin-bottom: 10px">
                🎥
                <span class="file-type">Upload Video</span>
                <span class="file-info">MP4, MOV (Max 20MB)</span>
            </div>
            <input type="file" id="video" name="video" accept="video/*" class="file-input">
        </label>
        <div class="file-preview" id="video-preview"  style="display: flex; margin-bottom: 10px"></div>
    </div>
</div>
        
        <input type="submit" value="Submit Request">
    </form>
</div>

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

    document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners to all file inputs
    const fileInputs = document.querySelectorAll('.file-input');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const previewDiv = document.getElementById(`${this.id}-preview`);
            const fileName = this.files[0]?.name || 'No file chosen';
            
            if (this.files.length > 0) {
                // Add has-file class for styling
                this.classList.add('has-file');
                
                // Show file info
                const file = this.files[0];
                const fileSize = (file.size / (1024 * 1024)).toFixed(2); // Convert to MB
                
                previewDiv.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span>${fileName}</span>
                        <span style="color: var(--green-apple);">${fileSize} MB</span>
                    </div>
                    <button type="button" onclick="removeFile('${this.id}')" 
                            style="background: rgba(255,107,107,0.2); color: #ff6b6b; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; margin-top: 5px; font-size: 0.8rem;">
                        Remove
                    </button>
                `;
            } else {
                previewDiv.innerHTML = '';
                this.classList.remove('has-file');
            }
        });
    });
});

function removeFile(inputId) {
    const input = document.getElementById(inputId);
    const previewDiv = document.getElementById(`${inputId}-preview`);
    
    input.value = '';
    previewDiv.innerHTML = '';
    input.classList.remove('has-file');
}
</script>

</body>
</html>