<?php
// filepath: c:\xampp\htdocs\parlor\admin\export_handler.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Access Denied.');
}

require_once '../includes/db_connect.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/parlor/vendor/autoload.php';

// --- Get and validate parameters ---
$type = $_GET['type'] ?? 'csv';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// --- Data Fetching ---
$where_clause = "WHERE a.status = 'completed' AND DATE(b.payment_time) BETWEEN ? AND ?";

// 1. Summary Stats
$stmt_summary = $conn->prepare("SELECT COALESCE(SUM(b.amount), 0) as total_revenue, COUNT(DISTINCT a.id) as total_appointments FROM bills b JOIN appointments a ON b.appointment_id = a.id $where_clause");
$stmt_summary->bind_param('ss', $start_date, $end_date);
$stmt_summary->execute();
$summary = $stmt_summary->get_result()->fetch_assoc();

// 2. Service Revenue
$stmt_service = $conn->prepare("SELECT s.name, COUNT(a.id) as appointment_count, COALESCE(SUM(b.amount), 0) as revenue FROM bills b JOIN appointments a ON b.appointment_id = a.id JOIN services s ON a.service_id = s.id $where_clause GROUP BY s.name ORDER BY revenue DESC");
$stmt_service->bind_param('ss', $start_date, $end_date);
$stmt_service->execute();
$service_revenue = $stmt_service->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. Employee Revenue
$stmt_employee = $conn->prepare("SELECT u.name, COUNT(a.id) as appointment_count, COALESCE(SUM(b.amount), 0) as revenue FROM bills b JOIN appointments a ON b.appointment_id = a.id JOIN users u ON a.employee_id = u.id $where_clause GROUP BY u.name ORDER BY revenue DESC");
$stmt_employee->bind_param('ss', $start_date, $end_date);
$stmt_employee->execute();
$employee_revenue = $stmt_employee->get_result()->fetch_all(MYSQLI_ASSOC);

// --- Generate File based on Type ---
$filename = "report_{$start_date}_to_{$end_date}";

if ($type === 'csv') {
    header('Content-Type: text/csv');
    header("Content-Disposition: attachment; filename={$filename}.csv");
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Revenue by Service']);
    fputcsv($output, ['Service', 'Appointments', 'Revenue']);
    foreach ($service_revenue as $row) { fputcsv($output, [$row['name'], $row['appointment_count'], $row['revenue']]); }
    fputcsv($output, []);
    fputcsv($output, ['Revenue by Employee']);
    fputcsv($output, ['Employee', 'Appointments', 'Revenue']);
    foreach ($employee_revenue as $row) { fputcsv($output, [$row['name'], $row['appointment_count'], $row['revenue']]); }
    fclose($output);
    exit;

} elseif ($type === 'pdf') {
    class PDF extends FPDF {
        private $brandColor = [106, 17, 203];

        function Header() {
            $logoPath = $_SERVER['DOCUMENT_ROOT'] . '/parlor/assets/images/logo.png';
            if (file_exists($logoPath)) {
                $this->Image($logoPath, 10, 8, 33);
            }
            $this->SetFont('Arial', 'B', 15);
            $this->SetTextColor($this->brandColor[0], $this->brandColor[1], $this->brandColor[2]);
            $this->Cell(80);
            $this->Cell(30, 10, 'Labonno Glamour World', 0, 1, 'C');
            $this->SetFont('Arial', '', 9);
            $this->SetTextColor(0, 0, 0);
            $this->Cell(80);
            $this->Cell(30, 5, 'Tongi, Gazipur-1710', 0, 1, 'C');
            $this->Cell(80);
            $this->Cell(30, 5, 'Phone: 01822679672', 0, 1, 'C');
            $this->Ln(10);
            $this->SetDrawColor($this->brandColor[0], $this->brandColor[1], $this->brandColor[2]);
            $this->Cell(0, 0, '', 'T', 1);
            $this->Ln(5);
        }

        function Footer() {
            $this->SetY(-20);
            $this->SetDrawColor($this->brandColor[0], $this->brandColor[1], $this->brandColor[2]);
            $this->Cell(0, 0, '', 'T', 1);
            $this->Ln(3);
            $this->SetFont('Arial', 'I', 8);
            $this->SetTextColor(128);
            $this->Cell(0, 5, 'www.labonnoglamourworld.com | Report Generated: ' . date('d M Y, h:i A'), 0, 0, 'L');
            $this->Cell(0, 5, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'R');
        }

        function ReportTitle($start_date, $end_date) {
            $this->SetFont('Arial', 'B', 16);
            $this->SetTextColor(0);
            $this->Cell(0, 10, 'Business Insights Report', 0, 1, 'C');
            $this->SetFont('Arial', '', 11);
            $this->Cell(0, 7, 'For the period: ' . date("d M, Y", strtotime($start_date)) . " to " . date("d M, Y", strtotime($end_date)), 0, 1, 'C');
            $this->Ln(10);
        }

        function SummaryBoxes($revenue, $appointments) {
            $this->SetFillColor(248, 249, 250);
            $this->SetDrawColor(222, 226, 230);
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(95, 8, 'TOTAL REVENUE', 'TLR', 0, 'C', true);
            $this->Cell(10, 8, '', 0, 0);
            $this->Cell(85, 8, 'COMPLETED APPOINTMENTS', 'TLR', 1, 'C', true);
            $this->SetFont('Arial', 'B', 20);
            $this->Cell(95, 15, mb_convert_encoding("৳", 'ISO-8859-1', 'UTF-8') . number_format($revenue, 2), 'BLR', 0, 'C');
            $this->Cell(10, 15, '', 0, 0);
            $this->Cell(85, 15, $appointments, 'BLR', 1, 'C');
            $this->Ln(15);
        }

        function ReportTable($title, $header, $data) {
            $this->SetFont('Arial', 'B', 12);
            $this->SetTextColor($this->brandColor[0], $this->brandColor[1], $this->brandColor[2]);
            $this->Cell(0, 10, $title, 0, 1);
            $this->SetFillColor($this->brandColor[0], $this->brandColor[1], $this->brandColor[2]);
            $this->SetTextColor(255);
            $this->SetDrawColor(222, 226, 230);
            $this->SetFont('Arial', 'B', 10);
            $w = [95, 45, 50];
            for($i = 0; $i < count($header); $i++) {
                $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
            }
            $this->Ln();
            $this->SetFillColor(240, 240, 240);
            $this->SetTextColor(0);
            $this->SetFont('Arial', '', 10);
            $fill = false;
            foreach($data as $row) {
                $this->Cell($w[0], 6, $row[0], 'LR', 0, 'L', $fill);
                $this->Cell($w[1], 6, $row[1], 'LR', 0, 'C', $fill);
                $this->Cell($w[2], 6, mb_convert_encoding("৳", 'ISO-8859-1', 'UTF-8') . number_format($row[2], 2), 'LR', 0, 'R', $fill);
                $this->Ln();
                $fill = !$fill;
            }
            $this->Cell(array_sum($w), 0, '', 'T');
            $this->Ln(10);
        }
    }

    $pdf = new PDF('P', 'mm', 'A4');
    $pdf->AliasNbPages();
    $pdf->AddPage();
    
    $pdf->ReportTitle($start_date, $end_date);
    $pdf->SummaryBoxes($summary['total_revenue'], $summary['total_appointments']);

    // Service Revenue Table
    $header_service = ['Service', 'Appointments', 'Revenue'];
    $data_service = array_map(function($row) { return [$row['name'], $row['appointment_count'], $row['revenue']]; }, $service_revenue);
    $pdf->ReportTable('Revenue by Service', $header_service, $data_service);

    // Employee Revenue Table
    $header_employee = ['Employee', 'Appointments', 'Revenue'];
    $data_employee = array_map(function($row) { return [$row['name'], $row['appointment_count'], $row['revenue']]; }, $employee_revenue);
    $pdf->ReportTable('Revenue by Employee', $header_employee, $data_employee);

    $pdf->Output('D', "{$filename}.pdf");
    exit;

} else {
    // Invalid type
    http_response_code(400);
    echo "Invalid export type specified. Use 'csv' or 'pdf'.";
    exit;
}