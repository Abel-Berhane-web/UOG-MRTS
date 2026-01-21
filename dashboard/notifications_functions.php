<?php
// notifications_functions.php

/**
 * Add a notification to a user
 */
function addNotification($conn, $user_id, $message, $link = null, $type = 'info') {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, link, type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $message, $link, $type);
    $stmt->execute();
}

/**
 * Get all notifications for a user
 */
function getUserNotifications($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}


/**
 * Get unread notification count
 */
function getUnreadCount($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res['unread_count'];
}

/**
 * Mark notification as read
 */
function markNotificationRead($conn, $notification_id) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $notification_id);
    $stmt->execute();
}

/**
 * Common notification triggers for MRTS
 */

/* Notify technician assigned */
function notifyTechnicianAssignedApp($conn, $tech_id, $request_id, $issue_title, $campus, $room) {
    // Verify the technician exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'technician'");
    $stmt->bind_param("i", $tech_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $msg = "You have been assigned to request '{$issue_title}' in {$campus}, Room {$room}.";
        $link = "tech_view_detail.php?id={$request_id}";
        addNotification($conn, $tech_id, $msg, $link, 'info');
    }
}


/* Notify requester request completed */
function notifyRequesterCompletedApp($conn, $user_id, $request_id, $issue_title) {
    // Verify user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $msg = "Your maintenance request '{$issue_title}' has been completed.";
        $link = "view_requests.php";
        addNotification($conn, $user_id, $msg, $link, 'success');
    }
}


/* Notify admin new user registration */
function notifyAdminNewRegistrationApp($conn, $fullname, $username, $role) {
    // Get all admin users
    $sql = "SELECT id FROM users WHERE role='admin'";
    $res = $conn->query($sql);

    while ($row = $res->fetch_assoc()) {
        $admin_id = $row['id'];  // Correct admin ID
        $msg = "New user '{$fullname}' ({$username}) registered as {$role}. Approve or reject.";
        $link = "admin_approve_users.php";

        // Add notification for this admin
        addNotification($conn, $admin_id, $msg, $link, 'warning');
    }
}

/* Notify external user price set by finance */
function notifyExternalPriceSetApp($conn, $user_id, $request_id, $issue_title, $price) {
    // Verify that the user exists and is an external user
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'external_user'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $msg = "Your request '{$issue_title}' now has a price of ETB {$price}. Please pay and upload payment proof.";
        $link = "external_payment_upload.php?id={$request_id}";
        addNotification($conn, $user_id, $msg, $link, 'info');
    }
}


/* Notify external user payment verified */
function notifyExternalPaymentVerifiedApp($conn, $user_id, $request_id, $issue_title) {
    // Verify the user exists and is an external user
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'external_user'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $msg = "Payment verified for your request '{$issue_title}'. Technician assignment will follow.";
        $link = "view_requests.php?id={$request_id}";
        addNotification($conn, $user_id, $msg, $link, 'success');
    }
}


/* Notify finance payment uploaded */
function notifyFinancePaymentUploadedApp($conn, $request_id, $issue_title, $user_name) {
    // Get all finance users
    $sql = "SELECT id FROM users WHERE role='finance'";
    $res = $conn->query($sql);

    while ($finance = $res->fetch_assoc()) {
        $finance_id = $finance['id']; // current finance user
        $msg = "Payment proof uploaded for request '{$issue_title}' by {$user_name}. Verify it.";
        $link = "finance_payment_verification.php?id={$request_id}";
        addNotification($conn, $finance_id, $msg, $link, 'info');
    }
}


// Notify finance when a new request needs price set
function notifyFinanceSetPrice($conn, $request_id, $request_title, $requester_name) {
    // Fetch all finance users
    $sql = "SELECT id FROM users WHERE role='finance'";
    $res = $conn->query($sql);

    while ($finance = $res->fetch_assoc()) {
        $finance_id = $finance['id'];
        $msg = "Request '{$request_title}' submitted by {$requester_name} requires price setting.";
        $link = "finance_pending_price.php?id={$request_id}"; // direct link to request
        addNotification($conn, $finance_id, $msg, $link, 'info');
    }
}


?>
