<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'chief_technician') {
    die("Unauthorized access.");
}

if (!isset($_GET['id'])) {
    die("No technician selected.");
}

$tech_id = intval($_GET['id']);

$conn = new mysqli("localhost", "root", "", "test_uog");
if ($conn->connect_error) die("DB connection failed: " . $conn->connect_error);

$stmt = $conn->prepare("SELECT id, fullname, email, username, specialization, phone, telegram, account_status, profile_image 
                        FROM users WHERE id=? AND role='technician'");
$stmt->bind_param("i", $tech_id);
$stmt->execute();
$result = $stmt->get_result();
$tech = $result->fetch_assoc();
$stmt->close();

if (!$tech) {
    die("Technician not found.");
}

// Get technician statistics
$stats_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned_count,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count
    FROM requests 
    WHERE assigned_technician_id = ?
");
$stats_stmt->bind_param("i", $tech_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stats_stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Profile | UoG MRTS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="view_technician.css"> 
    <style>
      
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-cog"></i> Technician Profile</h1>
            <p>Detailed information about the technician</p>
        </div>
        
        <div class="profile-container">
            <!-- Profile Sidebar -->
            <div class="profile-card">
                <?php if (!empty($tech['profile_image'])): ?>
                    <img src="uploads/<?= htmlspecialchars($tech['profile_image']) ?>" 
                         alt="Profile Image" class="profile-image">
                <?php else: ?>
                    <div class="profile-image" style="background: linear-gradient(135deg, var(--green-apple), var(--green-dark)); 
                                                    display: flex; align-items: center; justify-content: center; font-size: 4rem;">
                        👨‍💼
                    </div>
                <?php endif; ?>
                
                <h2 class="profile-name"><?= htmlspecialchars($tech['fullname']) ?></h2>
                <div class="profile-specialization"><?= htmlspecialchars($tech['specialization'] ?? 'General Technician') ?></div>
                
                <div class="status-badge <?= $tech['account_status'] == 'enabled' ? 'status-enabled' : 'status-disabled' ?>">
                    <i class="fas fa-<?= $tech['account_status'] == 'enabled' ? 'check-circle' : 'times-circle' ?>"></i>
                    <?= ucfirst($tech['account_status']) ?>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?= $stats['total_requests'] ?? 0 ?></div>
                        <div class="stat-label">Total Requests</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= $stats['assigned_count'] ?? 0 ?></div>
                        <div class="stat-label">Assigned</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= $stats['in_progress_count'] ?? 0 ?></div>
                        <div class="stat-label">In Progress</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= $stats['completed_count'] ?? 0 ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <a href="view_technician_requests.php?id=<?= $tech['id'] ?>" class="btn btn-primary">
                        <i class="fas fa-tasks"></i> View Requests
                    </a>
                </div>
            </div>
            
            <!-- Details Section -->
            <div class="details-card">
                <h3><i class="fas fa-info-circle"></i> Personal Information</h3>
                
                <div class="detail-group">
                    <div class="detail-item">
                        <span class="detail-label">Full Name</span>
                        <div class="detail-value"><?= htmlspecialchars($tech['fullname']) ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Username</span>
                        <div class="detail-value"><?= htmlspecialchars($tech['username']) ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Technician ID</span>
                        <div class="detail-value">#<?= $tech['id'] ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Specialization</span>
                        <div class="detail-value"><?= htmlspecialchars($tech['specialization'] ?? 'Not specified') ?></div>
                    </div>
                </div>
                
                <div class="contact-info">
                    <h4><i class="fas fa-address-card"></i> Contact Information</h4>
                    
                    <div class="detail-item">
                        <div class="icon-value">
                            <i class="fas fa-envelope"></i>
                            <span class="detail-value"><?= htmlspecialchars($tech['email']) ?></span>
                        </div>
                    </div>
                    
                    <?php if (!empty($tech['phone'])): ?>
                    <div class="detail-item">
                        <div class="icon-value">
                            <i class="fas fa-phone"></i>
                            <span class="detail-value"><?= htmlspecialchars($tech['phone']) ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($tech['telegram'])): ?>
                    <div class="detail-item">
                        <div class="icon-value">
                            <i class="fab fa-telegram"></i>
                            <span class="detail-value"><a  style="color: var(--text);
            font-size: 1.05rem;
            word-break: break-word; text-decoration: none " href="https://t.me/<?= htmlspecialchars($tech['telegram']) ?>" target="_blank">
                                    @<?= htmlspecialchars($tech['telegram']) ?></span>

                            
                                </a>

                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <a href="cheif_manage_technicians.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Technicians
                </a>
            </div>
        </div>
    </div>
</body>
</html>