<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require 'db.php';

$txn_id  = intval($_GET['txn'] ?? 0);
$user_id = $_SESSION['user_id'];

if (!$txn_id) { die('Invalid receipt.'); }

// Fetch transaction (only accessible if user is sender or receiver)
$stmt = $pdo->prepare("
    SELECT t.*, 
           s.full_name AS sender_name, s.account_no AS sender_acc, s.email AS sender_email,
           r.full_name AS receiver_name, r.account_no AS receiver_acc, r.email AS receiver_email
    FROM transactions t
    LEFT JOIN users s ON t.sender_id = s.id
    LEFT JOIN users r ON t.receiver_id = r.id
    WHERE t.id = ? AND (t.sender_id = ? OR t.receiver_id = ?) AND t.status = 'success'
");
$stmt->execute([$txn_id, $user_id, $user_id]);
$txn = $stmt->fetch();

if (!$txn) { die('Transaction not found or not accessible.'); }

// --- FPDF ---
require_once 'lib/fpdf.php';

class PDF extends FPDF {
    function Header() {
        // Gold header bar
        $this->SetFillColor(200, 169, 110);
        $this->Rect(0, 0, 210, 28, 'F');
        $this->SetFont('Helvetica', 'B', 18);
        $this->SetTextColor(9, 9, 15);
        $this->SetXY(0, 8);
        $this->Cell(0, 12, 'VaultX', 0, 0, 'C');
        $this->SetFont('Helvetica', '', 9);
        $this->SetXY(0, 18);
        $this->Cell(0, 6, 'TRANSACTION RECEIPT', 0, 0, 'C');
        $this->SetTextColor(30, 30, 40);
        $this->Ln(20);
    }
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Helvetica', 'I', 8);
        $this->SetTextColor(150, 150, 160);
        $this->Cell(0, 10, 'VaultX Digital Wallet | This is a system-generated receipt. | Page '.$this->PageNo(), 0, 0, 'C');
    }
    function SectionTitle($title) {
        $this->SetFont('Helvetica', 'B', 9);
        $this->SetTextColor(200, 169, 110);
        $this->SetFillColor(22, 22, 31);
        $this->Cell(0, 7, strtoupper($title), 0, 1, 'L', true);
        $this->SetTextColor(30, 30, 40);
        $this->Ln(1);
    }
    function InfoRow($label, $value, $highlight = false) {
        $this->SetFont('Helvetica', '', 9);
        $this->SetTextColor(120, 120, 140);
        $this->Cell(55, 7, $label, 0, 0);
        if ($highlight) {
            $this->SetFont('Helvetica', 'B', 10);
            $this->SetTextColor(200, 169, 110);
        } else {
            $this->SetFont('Helvetica', '', 9);
            $this->SetTextColor(30, 30, 40);
        }
        $this->Cell(0, 7, $value, 0, 1);
    }
}

$pdf = new PDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 20);

// Status Badge
$pdf->SetFillColor(74, 222, 128);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Helvetica', 'B', 9);
$pdf->SetX(140);
$pdf->Cell(55, 7, '✓  SUCCESSFUL', 1, 1, 'C', true);
$pdf->Ln(4);

// TRANSACTION DETAILS
$pdf->SectionTitle('Transaction Details');
$pdf->InfoRow('Transaction ID', '#' . str_pad($txn['id'], 8, '0', STR_PAD_LEFT));
$pdf->InfoRow('Date & Time', date('F d, Y  H:i:s', strtotime($txn['created_at'])));
$pdf->InfoRow('Amount', 'PKR ' . number_format($txn['amount'], 2), true);
$pdf->InfoRow('Note', $txn['note'] ?: '—');
$pdf->InfoRow('Status', 'Successful');
$pdf->Ln(4);

// SENDER
$pdf->SectionTitle('Sender');
$pdf->InfoRow('Name', $txn['sender_name']);
$pdf->InfoRow('Account No', $txn['sender_acc']);
$pdf->InfoRow('Email', $txn['sender_email']);
$pdf->Ln(4);

// RECEIVER
$pdf->SectionTitle('Receiver');
$pdf->InfoRow('Name', $txn['receiver_name']);
$pdf->InfoRow('Account No', $txn['receiver_acc']);
$pdf->InfoRow('Email', $txn['receiver_email']);
$pdf->Ln(6);

// Divider
$pdf->SetDrawColor(200, 169, 110);
$pdf->SetLineWidth(0.5);
$pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
$pdf->Ln(4);

$pdf->SetFont('Helvetica', 'I', 8);
$pdf->SetTextColor(120, 120, 140);
$pdf->Cell(0, 6, 'Keep this receipt as proof of your transaction. Receipt ID: VLT-RCT-' . $txn_id . '-' . date('Ymd'), 0, 1, 'C');

$filename = 'VaultX_Receipt_TXN' . str_pad($txn_id, 8, '0', STR_PAD_LEFT) . '.pdf';
$pdf->Output('D', $filename);
exit;
?>
