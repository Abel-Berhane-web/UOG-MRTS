<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

$user_id = $_SESSION['user_id'];

$conn = new mysqli("localhost", "root", "", "test_uog");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all requests by the user
$sql = "SELECT id, issue_title, category, campus, building_number, room_number, status, created_at 
        FROM requests 
        WHERE requested_by = ?
        ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests - UoG MRTS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="view_requests.css"> 
<style>
        
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-tools"></i> My Maintenance Requests</h1>
            <p>View and manage all your maintenance requests in one place</p>
        </div>
        
        <div class="filters">
            <button class="filter-btn active" data-filter="all">All Requests</button>
            <button class="filter-btn" data-filter="Pending Assignment">Pending Assignment</button>
            <button class="filter-btn" data-filter="Assigned">Assigned</button>
            <button class="filter-btn" data-filter="In Progress">In Progress</button>
            <button class="filter-btn" data-filter="Completed">Completed</button>
            
        </div>
        
        <?php if ($result->num_rows > 0): ?>
            <div class="requests-grid">
                <?php while ($row = $result->fetch_assoc()): 
                    // Determine status class
                    $status_class = 'status-' . strtolower(str_replace(' ', '-', $row['status']));
                ?>
                    <div class="request-card" data-status="<?= htmlspecialchars($row['status']) ?>">
                        <div class="card-header">
                            <div>
                                <h3 class="request-title"><?= htmlspecialchars($row['issue_title']) ?></h3>
                                <span class="request-id">ID: #<?= $row['id'] ?></span>
                            </div>
                            <span class="status-badge <?= $status_class ?>"><?= htmlspecialchars($row['status']) ?></span>
                        </div>
                        
                        <div class="request-details">
                            <div class="detail-item">
                                <span class="detail-label">Category</span>
                                <span class="detail-value"><?= htmlspecialchars($row['category']) ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Campus</span>
                                <span class="detail-value"><?= htmlspecialchars($row['campus']) ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Building</span>
                                <span class="detail-value"><?= htmlspecialchars($row['building_number']) ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Room</span>
                                <span class="detail-value"><?= htmlspecialchars($row['room_number']) ?></span>
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <span class="request-date">
                                <i class="far fa-calendar"></i> 
                                <?= date('M j, Y', strtotime($row['created_at'])) ?>
                            </span>
                            <a href="request_detail.php?id=<?= $row['id'] ?>" class="view-btn">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-requests">
                <i class="fas fa-clipboard-list"></i>
                <h2>No requests yet</h2>
                <p>You haven't submitted any maintenance requests yet.</p>
                <a href="external_user.php" class="new-request-btn">
                    <i class="fas fa-plus"></i> Create New Request
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const requestCards = document.querySelectorAll('.request-card');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const filter = this.getAttribute('data-filter');
                    
                    // Update active button
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Filter cards
                    requestCards.forEach(card => {
                        if (filter === 'all' || card.getAttribute('data-status') === filter) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });
            
            // Add animation to cards
            requestCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>