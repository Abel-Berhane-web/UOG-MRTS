<?php
// email_functions.php
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send a generic email
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body HTML body content
 * @return bool
 */
function sendEmailNotification($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'berhaneabel53@gmail.com'; // your email
        $mail->Password   = 'wirmkovvqklqdptg'; // your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Sender info
        $mail->setFrom('berhaneabel53@gmail.com', 'UOG-MRTS System');
        $mail->addAddress($to);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        return $mail->send();
    } catch (Exception $e) {
        error_log("Email could not be sent. Error: {$mail->ErrorInfo}");
        echo "Mailer Error: {$mail->ErrorInfo}"; // dev only
        return false;
    }
}

/**
 * Notify admin when a user account is locked
 */
function notifyAdminAccountLocked($conn, $username) {
    // Fetch admin emails from DB
    $admins = $conn->query("SELECT email FROM users WHERE role='admin'");
    if ($admins->num_rows > 0) {
        while ($row = $admins->fetch_assoc()) {
            $to = $row['email'];
            $subject = "User Account Locked - UOG-MRTS";
            $body = "<h2>Account Locked</h2>
                     <p>User <strong>{$username}</strong> has been locked due to multiple failed login attempts.</p>";
            sendEmailNotification($to, $subject, $body);
        }
    }
}

/**
 * Notify user that their account was locked
 */
function notifyUserAccountLocked($user_email, $fullname) {
    $subject = "Your Account Has Been Locked - UOG-MRTS";
    $body = "<h2>Account Locked</h2>
             <p>Dear {$fullname},</p>
             <p>Your account has been temporarily locked due to multiple failed login attempts.</p>
             <p>Please contact the administrator to re-enable your account.</p>";
    sendEmailNotification($user_email, $subject, $body);
}



/**
 * Notify technician of assignment
 */
function notifyTechnicianAssigned($technician_email, $request_id, $issue_title, $category, $campus, $room_number) {
    $subject = "UOG-MRTS New Maintenance Request Assigned - #$request_id";
    $body = "
        <h2>New Request Assigned</h2>
        <p><strong>Title:</strong> {$issue_title}</p>
        <p><strong>Category:</strong> {$category}</p>
        <p><strong>Campus:</strong> {$campus}</p>
        <p><strong>Room Number:</strong> {$room_number}</p>
        <p>Please <a href='http://localhost/uog/dashboard/tech_view_detail.php?id={$request_id}'>click here</a> to view the request.</p>
    ";
    return sendEmailNotification($technician_email, $subject, $body);
}

/**
 * Notify requester of completion
 */
function notifyRequesterCompleted($requester_email, $request_id, $issue_title) {
    $subject = "UOG-MRTS Maintenance Request Completed - #$request_id";
    $body = "
        <h2>Request Completed</h2>
        <p>Your maintenance request <strong>{$issue_title}</strong> has been completed.</p>
        <p>Request ID: {$request_id}</p>
        <p>Thank you for using the UOG-MRTS system.</p>
    ";
    return sendEmailNotification($requester_email, $subject, $body);
}

/**
 * Notify admin when a new user registers
 */
function notifyAdminNewRegistration($admin_email, $fullname, $username, $email, $role) {
    $subject = "New User Registration Pending Approval";
    $body = "
        <h2>New User Registered</h2>
        <p><strong>Name:</strong> {$fullname}</p>
        <p><strong>Username:</strong> {$username}</p>
        <p><strong>Email:</strong> {$email}</p>
        <p><strong>Role:</strong> {$role}</p>
        <p><a href='http://localhost/uog/admin_approve_users.php'>Approve or Reject this user</a></p>
    ";
    return sendEmailNotification($admin_email, $subject, $body);
}

/**
 * Notify user when account is approved
 */
function notifyUserAccountApproved($user_email, $fullname) {
    $subject = "Your UOG-MRTS Account Has Been Approved";
    $body = "
        <h2>Account Approved</h2>
        <p>Dear {$fullname},</p>
        <p>Your account has been approved. You can now log in and use the system.</p>
        <p><a href='http://localhost/uog/login.php'>Click here to log in</a></p>
    ";
    return sendEmailNotification($user_email, $subject, $body);
}
/**
 * Send temporary password for forgot password
 */
function notifyUserTemporaryPassword($user_email, $fullname, $temp_password) {
    $subject = "UOG-MRTS Temporary Password";
    $body = "
        <h2>Password Reset Request</h2>
        <p>Dear {$fullname},</p>
        <p>We received a request to reset your password. Your temporary password is:</p>
        <p><strong>{$temp_password}</strong></p>
        <p>Please log in using this password and change it immediately.</p>
        <p><a href='http://localhost/uog/login.php'>Click here to log in</a></p>
    ";
    return sendEmailNotification($user_email, $subject, $body);
}

function notifyFinanceNewRequest($finance_email, $request_id, $issue_title, $requester_name) {
    $subject = "UOG-MRTS Request Price - #$request_id";
    $body = "
        <h2>New Maintenance Request</h2>
        <p><strong>Title:</strong> {$issue_title}</p>
        <p><strong>Requester:</strong> {$requester_name}</p>
        <p>Please <a href='http://localhost/uog/finance_pending_price.php'>set the price</a> for this request.</p>
    ";
    return sendEmailNotification($finance_email, $subject, $body);
}

function notifyExternalPriceSet($user_email, $request_id, $issue_title, $price, $instructions) {
    $subject = "UOG-MRTS Price for Your Maintenance Request - #$request_id";
    $body = "
        <h2>Price Assigned</h2>
        <p>Your maintenance request <strong>{$issue_title}</strong> now has a price of <strong>ETB {$price}</strong>.</p>
        <p>Payment Instructions: {$instructions}</p>
        <p>Please <a href='http://localhost/uog/external_payment_upload.php'>upload your payment proof</a>.</p>
    ";
    return sendEmailNotification($user_email, $subject, $body);
}

function notifyFinancePaymentUploaded($finance_email, $request_id, $issue_title, $user_name) {
    $subject = "UOG-MRTS Payment Proof Uploaded - #$request_id";
    $body = "
        <h2>Payment Uploaded</h2>
        <p>External user <strong>{$user_name}</strong> has uploaded payment proof for request <strong>{$issue_title}</strong>.</p>
        <p>Please <a href='http://localhost/uog/finance_payment_verification.php'>verify the payment</a>.</p>
    ";
    return sendEmailNotification($finance_email, $subject, $body);
}

function notifyExternalPaymentVerified($user_email, $request_id, $issue_title) {
    $subject = "UOG-MRTS Payment Verified - #$request_id";
    $body = "
        <h2>Payment Verified</h2>
        <p>Your payment for maintenance request <strong>{$issue_title}</strong> has been verified by finance.</p>
        <p>The request will now proceed to technician assignment.</p>
        <p>Thank you for using UOG-MRTS!</p>
    ";
    return sendEmailNotification($user_email, $subject, $body);
}

?>
