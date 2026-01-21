<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'external_user') {
    die("Unauthorized access.");
}

require('fpdf/fpdf.php'); // Make sure FPDF library is in your project

$conn = new mysqli("localhost", "root", "", "test_uog");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Get request_id from GET
$request_id = $_GET['request_id'] ?? null;

if (!$request_id) {
    die("❌ Invalid request. Missing Request ID.");
}

// Fetch joined info (payment + request + user)
$stmt = $conn->prepare("
    SELECT r.id AS request_id, r.price_status, r.issue_title, r.created_at,
           u.fullname, u.email, u.phone,
           p.verified_status, p.tx_ref, p.price
    FROM requests r
    INNER JOIN users u ON r.requested_by = u.id
    LEFT JOIN paymentproof p ON r.id = p.request_id
    WHERE r.id = ?
");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();
$payment = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$payment || !$payment['verified_status']) {
    die("❌ Payment not verified yet. Cannot generate receipt.");
}

// Format dates
$request_date = date('F j, Y', strtotime($payment['created_at']));
$payment_date = isset($payment['payment_date']) ? date('F j, Y', strtotime($payment['payment_date'])) : date('F j, Y');
$receipt_number = 'RCPT-' . str_pad($payment['request_id'], 6, '0', STR_PAD_LEFT);

// Create PDF with enhanced styling
class EnhancedPDF extends FPDF {
    function Header() {
        // University logo and header
        $this->SetFont('Arial', 'B', 20);
        $this->SetTextColor(44, 185, 85); // Green color
        $this->Cell(0, 15, 'University of Gondar', 0, 1, 'C');
        
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(18, 79, 41); // Dark green
        $this->Cell(0, 10, 'Maintenance Request Tracking System', 0, 1, 'C');
        
        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor(0, 0, 0); // Black
        $this->Cell(0, 15, 'PAYMENT RECEIPT', 0, 1, 'C');
        
        // Line separator
        $this->SetDrawColor(44, 185, 85);
        $this->SetLineWidth(0.5);
        $this->Line(10, 45, 200, 45);
        $this->Ln(10);
    }
    
    function Footer() {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
    
    function DetailRow($label, $value, $boldValue = false) {
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(60, 10, $label, 0, 0);
        
        if ($boldValue) {
            $this->SetFont('Arial', 'B', 12);
        } else {
            $this->SetFont('Arial', '', 12);
        }
        
        $this->Cell(0, 10, $value, 0, 1);
        $this->SetFont('Arial', '', 12);
    }
}

$pdf = new EnhancedPDF();
$pdf->AliasNbPages(); // For page numbering
$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);

// Receipt information
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Receipt Information', 0, 1);
$pdf->SetFont('Arial', '', 12);

$pdf->DetailRow('Receipt Number:', $receipt_number);
$pdf->DetailRow('Issue Date:', $payment_date);
$pdf->DetailRow('Request ID:', '#' . $payment['request_id']);
$pdf->DetailRow('Request Date:', $request_date);
$pdf->DetailRow('Transaction Reference:', $payment['tx_ref'] ?? 'N/A');

$pdf->Ln(8);

// Request details
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Request Details', 0, 1);
$pdf->SetFont('Arial', '', 12);

$pdf->DetailRow('Request Title:', $payment['issue_title'] ?? 'Maintenance Request');
$pdf->DetailRow('Payment Status:', $payment['verified_status']);

$pdf->Ln(8);

// User information
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'User Information', 0, 1);
$pdf->SetFont('Arial', '', 12);

$pdf->DetailRow('Full Name:', $payment['fullname']);
$pdf->DetailRow('Email Address:', $payment['email']);
$pdf->DetailRow('Phone Number:', $payment['phone'] ?? 'N/A');

$pdf->Ln(8);

// Payment amount with highlighted box
$pdf->SetFillColor(240, 245, 240); // Light green background
$pdf->SetDrawColor(44, 185, 85); // Green border
$pdf->SetLineWidth(0.5);
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 15, 'Payment Amount', 1, 1, 'C', true);
$pdf->SetFont('Arial', 'B', 20);
$pdf->SetTextColor(44, 185, 85); // Green color for amount
$pdf->Cell(0, 15, ($payment['price'] ?? $payment['price_status']) . ' ETB', 1, 1, 'C');
$pdf->SetTextColor(0, 0, 0); // Reset to black

$pdf->Ln(10);

// Thank you message
$pdf->SetFont('Arial', 'I', 12);
$pdf->Cell(0, 10, 'Thank you for your payment! This receipt confirms that your payment has been successfully processed.', 0, 1, 'C');

$pdf->Ln(5);

// Footer note
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(128, 128, 128);
$pdf->MultiCell(0, 8, 'Please keep this receipt for your records. For any inquiries, contact support@uog-mrts.edu.et', 0, 'C');

// Output PDF to browser for download
$pdf->Output('D', 'UoG_Receipt_' . ($payment['tx_ref'] ?? $payment['request_id']) . '.pdf');
?>