<?php
session_start();

// Only finance staff can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'finance') {
    die("Unauthorized access.");
}

$conn = new mysqli("localhost", "root", "", "test_uog");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all external requests pending price
$sql = "
SELECT 
    r.id, 
    r.issue_title, 
    r.issue_description, 
    u.username, 
    r.campus, 
    r.building_number, 
    r.room_number, 
    r.created_at
FROM requests r
JOIN users u ON r.requested_by = u.id
WHERE r.user_type = 'external'
  AND r.price_status = 'Not Set'
ORDER BY r.created_at ASC;
";

$result = $conn->query($sql);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $request_id = $_POST['request_id'];
    $price = $_POST['price'];
    $payment_instructions = $_POST['payment_instructions'];
    
    $update_sql = "UPDATE requests SET price = ?, payment_instructions = ?, price_status = 'Set' WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("dsi", $price, $payment_instructions, $request_id);
    
    if ($stmt->execute()) {
        $success_message = "Price set successfully for request #$request_id";
    } else {
        $error_message = "Error setting price: " . $conn->error;
    }
    
    // Refresh the page to show updated list
    header("Location: finance_set_price.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance - Set Prices | UoG MRTS</title>
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
      <link rel="stylesheet" href="finance_pending_price.css">  
    <style>
      
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Finance Dashboard</h1>
            <p class="subtitle">Set prices for external maintenance requests</p>
        </header>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-label">Total Pending Requests</div>
                <div class="stat-number"><?php echo $result->num_rows; ?></div>
                <div class="stat-label">Awaiting Pricing</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Finance Role</div>
                <div class="stat-number"><?php echo $_SESSION['username'] ?? 'User'; ?></div>
                <div class="stat-label">Logged In</div>
            </div>
        </div>
        
        <h2>Requests Pending Price</h2>
        
        <?php if ($result->num_rows > 0): ?>
            <table class="requests-table">
                <thead>
                    <tr>
                        <th>Request Details</th>
                        <th>Requested By</th>
                        <th>Location</th>
                        <th>Submitted On</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div class="request-title"><?= htmlspecialchars($row['issue_title']) ?></div>
                            <div class="request-desc"><?= nl2br(htmlspecialchars($row['issue_description'])) ?></div>
                        </td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td>
                            <div class="location"><?= htmlspecialchars($row['campus']) ?></div>
                            <div class="location">Building <?= htmlspecialchars($row['building_number']) ?>, Room <?= htmlspecialchars($row['room_number']) ?></div>
                        </td>
                        <td>
                            <div class="date"><?= date('M j, Y', strtotime($row['created_at'])) ?></div>
                            <div class="date"><?= date('g:i A', strtotime($row['created_at'])) ?></div>
                        </td>
                        <td>
                            <form action="finance_set_price.php" method="POST" class="price-form">
                                <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                                
                                <div class="form-group">
                                    <label for="price-<?= $row['id'] ?>">Price (ETB)</label>
                                    <input type="number" id="price-<?= $row['id'] ?>" name="price" placeholder="0.00" step="0.01" min="0" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="instructions-<?= $row['id'] ?>">Payment Instructions</label>
                                    <textarea id="instructions-<?= $row['id'] ?>" name="payment_instructions" placeholder="Enter payment instructions..." rows="3" required></textarea>
                                </div>
                                
                                <button type="submit">Set Price</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-requests">
                <h3>No requests pending pricing</h3>
                <p>All external requests have been priced.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Add confirmation before submitting forms
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const price = this.querySelector('input[name="price"]').value;
                if (!confirm(`Set price to ETB ${price}? This action cannot be undone.`)) {
                    e.preventDefault();
                }
            });
        });
        
        // Format price input on blur
        document.querySelectorAll('input[name="price"]').forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value) {
                    this.value = parseFloat(this.value).toFixed(2);
                }
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>