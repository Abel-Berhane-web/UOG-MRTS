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

// Get technician info
$stmt = $conn->prepare("SELECT fullname, specialization, profile_image FROM users WHERE id=? AND role='technician'");
$stmt->bind_param("i", $tech_id);
$stmt->execute();
$tech = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$tech) die("Technician not found.");

// Fetch technician requests
$stmt = $conn->prepare("SELECT id, issue_title, category, campus, building_number, room_number, status, created_at 
                        FROM requests 
                        WHERE assigned_technician_id=? 
                        ORDER BY FIELD(status,'assigned','in_progress','completed'), created_at DESC");
$stmt->bind_param("i", $tech_id);
$stmt->execute();
$result = $stmt->get_result();
$total_requests = $result->num_rows;

// Get status counts
$status_stmt = $conn->prepare("
    SELECT 
        status,
        COUNT(*) as count
    FROM requests 
    WHERE assigned_technician_id=?
    GROUP BY status
");
$status_stmt->bind_param("i", $tech_id);
$status_stmt->execute();
$status_result = $status_stmt->get_result();

$status_counts = [
    'assigned' => 0,
    'in_progress' => 0,
    'completed' => 0
];

while ($row = $status_result->fetch_assoc()) {
    $status_counts[$row['status']] = $row['count'];
}
$status_stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Requests | UoG MRTS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="view_technician_requests.css"> 
    <style>
       
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-tasks"></i> Technician Requests</h1>
            <p>View all maintenance requests assigned to this technician</p>
        </div>
        
        <div class="technician-header">
            <?php if (!empty($tech['profile_image'])): ?>
                <img src="uploads/<?= htmlspecialchars($tech['profile_image']) ?>" 
                     alt="Technician Image" class="technician-image">
            <?php else: ?>
                <div class="technician-image" style="background: linear-gradient(135deg, var(--green-apple), var(--green-dark)); 
                                                  display: flex; align-items: center; justify-content: center; font-size: 2rem;">
                    👨‍💼
                </div>
            <?php endif; ?>
            
            <div class="technician-info">
                <h2 class="technician-name"><?= htmlspecialchars($tech['fullname']) ?></h2>
                <div class="technician-specialization"><?= htmlspecialchars($tech['specialization']) ?></div>
            </div>
        </div>
        
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-label">Total Requests</div>
                <div class="stat-number stat-total"><?= $total_requests ?></div>
                <div class="stat-label">All assigned work</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Assigned</div>
                <div class="stat-number stat-assigned"><?= $status_counts['assigned'] ?></div>
                <div class="stat-label">Awaiting action</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">In Progress</div>
                <div class="stat-number stat-progress"><?= $status_counts['in_progress'] ?></div>
                <div class="stat-label">Currently working</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Completed</div>
                <div class="stat-number stat-completed"><?= $status_counts['completed'] ?></div>
                <div class="stat-label">Finished tasks</div>
            </div>
        </div>
        
        <?php if ($result->num_rows > 0): ?>
            <div style="overflow-x: auto;">
                <table class="requests-table">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Issue Title</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Created Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= $row['id'] ?></td>
                                <td>
                                    <div style="font-weight: 600;"><?= htmlspecialchars($row['issue_title']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($row['category']) ?></td>
                                <td>
                                    <div class="location-info"><?= htmlspecialchars($row['campus']) ?></div>
                                    <div class="location-info">Bldg <?= htmlspecialchars($row['building_number']) ?>, Rm <?= htmlspecialchars($row['room_number']) ?></div>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= str_replace('_', '-', $row['status']) ?>">
                                        <?= ucfirst(str_replace("_", " ", $row['status'])) ?>
                                    </span>
                                </td>
                                <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-requests">
                <h3><i class="fas fa-inbox"></i> No Requests Assigned</h3>
                <p>This technician doesn't have any assigned maintenance requests yet.</p>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="cheif_manage_technicians.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Technicians
            </a>
        </div>
    </div>

    <script>
        // Add simple filtering functionality
        document.addEventListener('DOMContentLoaded', function() {
            const table = document.querySelector('.requests-table');
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                
                // Could add filter buttons for status in a real implementation
                console.log('Table with', rows.length, 'rows loaded');
            }
        });
    </script>
</body>
</html>