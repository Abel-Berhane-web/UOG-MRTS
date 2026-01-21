<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$conn = new mysqli("localhost", "root", "", "test_uog");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$success_msg = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'];
    $username = $_POST['username'];
    $phone = $_POST['phone'];
    $telegram = $_POST['telegram'];

    // Update basic info
    $stmt = $conn->prepare("UPDATE users SET fullname = ?, username = ?, phone = ?, telegram = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $fullname, $username, $phone, $telegram, $user_id);
    $stmt->execute();
    $stmt->close();

    // Handle profile image upload
    if (!empty($_FILES['profile_image']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = time() . "_" . basename($_FILES["profile_image"]["name"]);
        $target_file = $target_dir . $file_name;

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES["profile_image"]["type"], $allowed_types)) {
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                $update_img = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                $update_img->bind_param("si", $file_name, $user_id);
                $update_img->execute();
                $update_img->close();
            }
        }
    }

    $success_msg = "Profile updated successfully!";
}

// Fetch user info
$stmt = $conn->prepare("SELECT fullname, username, phone, telegram, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - UoG MRTS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --green-apple: #2CB955;
            --green-dark: #124F29;
            --bg: #0b1510;
            --text: #eaf7ee;
            --muted: #bfe9ca;
            --card: #0f2016;
            --shadow: 0 10px 30px rgba(0,0,0,.35);
            --radius: 16px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, var(--bg), #0f1a13);
            color: var(--text);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 2.2rem;
            color: var(--green-apple);
            margin-bottom: 10px;
        }
        
        .header p {
            color: var(--muted);
            font-size: 1.1rem;
        }
        
        .profile-card {
            background: var(--card);
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255,255,255,0.06);
        }
        
        .success-message {
            background: rgba(0,255,0,0.08);
            color: #b3ffb3;
            border: 1px solid #4dff4d;
            border-radius: 8px;
            padding: 12px 18px;
            margin-bottom: 25px;
            text-align: center;
        }
        
        .image-section {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .profile-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--green-apple);
            box-shadow: var(--shadow);
            margin-bottom: 15px;
        }
        
        .file-input-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(44, 185, 85, 0.1);
            color: var(--green-apple);
            border: 1px solid var(--green-apple);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .file-input-label:hover {
            background: var(--green-apple);
            color: #07150d;
        }
        
        .file-input {
            display: none;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--muted);
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: var(--text);
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--green-apple);
            box-shadow: 0 0 0 2px rgba(44, 185, 85, 0.2);
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.06);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--green-apple), var(--green-dark));
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(44, 185, 85, 0.3);
        }
        
        .btn-secondary {
            background: rgba(255,255,255,0.05);
            color: var(--text);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .btn-secondary:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .support-text {
            text-align: center;
            color: var(--muted);
            font-size: 0.9rem;
            margin-top: 10px;
        }
        
        @media (max-width: 768px) {
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-edit"></i> Edit Profile</h1>
            <p>Update your personal information and profile picture</p>
        </div>
        
        <div class="profile-card">
            <?php if (!empty($success_msg)): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> <?= $success_msg ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="image-section">
                    <?php if (!empty($user['profile_image'])): ?>
                        <img src="uploads/<?= htmlspecialchars($user['profile_image']) ?>" 
                             alt="Profile Image" class="profile-img" id="imagePreview">
                    <?php else: ?>
                        <div class="profile-img" style="background: linear-gradient(135deg, var(--green-apple), var(--green-dark)); 
                                                      display: flex; align-items: center; justify-content: center; font-size: 3rem;" 
                             id="imagePreview">
                            👤
                        </div>
                    <?php endif; ?>
                    <div></div>
                    <label for="profile_image" class="file-input-label">
                        <i class="fas fa-camera"></i> Change Photo
                    </label>
                    <input type="file" id="profile_image" name="profile_image" accept="image/*" class="file-input">
                    <p class="support-text">Supported formats: JPG, PNG, GIF</p>
                </div>
                
                <div class="form-group">
                    <label for="fullname" class="form-label">Full Name</label>
                    <input type="text" id="fullname" name="fullname" class="form-input" 
                           value="<?= htmlspecialchars($user['fullname']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" id="username" name="username" class="form-input" 
                           value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="text" id="phone" name="phone" class="form-input" 
                           value="<?= htmlspecialchars($user['phone']) ?>">
                </div>
                
                <div class="form-group">
                    <label for="telegram" class="form-label">Telegram Username</label>
                    <input type="text" id="telegram" name="telegram" class="form-input" 
                           value="<?= htmlspecialchars($user['telegram']) ?>" 
                           placeholder="@username">
                </div>
                
                <div class="form-actions">
                    <a href="profile.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Image preview functionality
        const imageInput = document.getElementById('profile_image');
        const imagePreview = document.getElementById('imagePreview');
        
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                
                reader.addEventListener('load', function() {
                    if (imagePreview.tagName === 'IMG') {
                        imagePreview.src = reader.result;
                    } else {
                        // Replace the div with an img element
                        const newImg = document.createElement('img');
                        newImg.src = reader.result;
                        newImg.className = 'profile-img';
                        newImg.alt = 'Profile Preview';
                        newImg.id = 'imagePreview';
                        imagePreview.parentNode.replaceChild(newImg, imagePreview);
                    }
                });
                
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>