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
$stmt_summary = $conn->prepare("
    SELECT 
        COALESCE(SUM(b.amount), 0) as total_revenue, 
        COUNT(DISTINCT a.id) as total_appointments,
        (SELECT COUNT(*) FROM users WHERE role='customer' AND DATE(created_at) BETWEEN ? AND ?) as new_customers
    FROM bills b 
    JOIN appointments a ON b.appointment_id = a.id 
    $where_clause");
$stmt_summary->bind_param('ssss', $start_date, $end_date, $start_date, $end_date);
$stmt_summary->execute();
$summary = $stmt_summary->get_result()->fetch_assoc();

// 2. Service Revenue
$stmt_service = $conn->prepare("
    SELECT 
        s.name, 
        COUNT(a.id) as appointment_count, 
        COALESCE(SUM(b.amount), 0) as revenue 
    FROM bills b 
    JOIN appointments a ON b.appointment_id = a.id 
    JOIN services s ON a.service_id = s.id 
    $where_clause 
    GROUP BY s.name 
    ORDER BY revenue DESC");
$stmt_service->bind_param('ss', $start_date, $end_date);
$stmt_service->execute();
$service_revenue = $stmt_service->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. Employee Revenue
$stmt_employee = $conn->prepare("
    SELECT 
        u.name, 
        COUNT(a.id) as appointment_count, 
        COALESCE(SUM(b.amount), 0) as revenue 
    FROM bills b 
    JOIN appointments a ON b.appointment_id = a.id 
    JOIN employees e ON a.employee_id = e.id
    JOIN users u ON e.user_id = u.id 
    $where_clause 
    GROUP BY u.name 
    ORDER BY revenue DESC");
$stmt_employee->bind_param('ss', $start_date, $end_date);
$stmt_employee->execute();
$employee_revenue = $stmt_employee->get_result()->fetch_all(MYSQLI_ASSOC);

// 4. NEW: Payment Methods
$stmt_payment = $conn->prepare("
    SELECT 
        b.payment_method, 
        COUNT(*) as count, 
        SUM(b.amount) as total 
    FROM bills b
    JOIN appointments a ON b.appointment_id = a.id
    $where_clause
    GROUP BY b.payment_method
    ORDER BY total DESC");
$stmt_payment->bind_param('ss', $start_date, $end_date);
$stmt_payment->execute();
$payment_methods = $stmt_payment->get_result()->fetch_all(MYSQLI_ASSOC);

// 5. NEW: Monthly trend (for the year)
$current_year = date('Y', strtotime($end_date));
$stmt_trend = $conn->prepare("
    SELECT 
        MONTH(b.payment_time) as month,
        SUM(b.amount) as revenue
    FROM bills b
    JOIN appointments a ON b.appointment_id = a.id
    WHERE a.status = 'completed' 
    AND YEAR(b.payment_time) = ?
    GROUP BY MONTH(b.payment_time)
    ORDER BY month");
$stmt_trend->bind_param('s', $current_year);
$stmt_trend->execute();
$monthly_trend = $stmt_trend->get_result()->fetch_all(MYSQLI_ASSOC);

// Convert to month names and fill in missing months
$all_months = [];
for ($i = 1; $i <= 12; $i++) {
    $month_name = date('F', mktime(0, 0, 0, $i, 1, $current_year));
    $found = false;
    foreach ($monthly_trend as $trend) {
        if ($trend['month'] == $i) {
            $all_months[] = [
                'month' => $month_name,
                'revenue' => $trend['revenue']
            ];
            $found = true;
            break;
        }
    }
    if (!$found) {
        $all_months[] = [
            'month' => $month_name,
            'revenue' => 0
        ];
    }
}

// --- Generate File based on Type ---
$filename = "business_report_{$start_date}_to_{$end_date}";

if ($type === 'csv') {
    header('Content-Type: text/csv');
    header("Content-Disposition: attachment; filename={$filename}.csv");
    $output = fopen('php://output', 'w');
    
    // Header and Summary
    fputcsv($output, ['Labonno Glamour World - Business Insights Report']);
    fputcsv($output, ['Period:', date("d M, Y", strtotime($start_date)) . " to " . date("d M, Y", strtotime($end_date))]);
    fputcsv($output, []);
    fputcsv($output, ['Total Revenue:', 'BDT ' . number_format($summary['total_revenue'], 2)]);
    fputcsv($output, ['Completed Appointments:', $summary['total_appointments']]);
    fputcsv($output, ['New Customers:', $summary['new_customers']]);
    fputcsv($output, []);
    
    // Revenue by Service
    fputcsv($output, ['Revenue by Service']);
    fputcsv($output, ['Service', 'Appointments', 'Revenue']);
    foreach ($service_revenue as $row) {
        fputcsv($output, [
            $row['name'], 
            $row['appointment_count'], 
            'BDT ' . number_format($row['revenue'], 2)
        ]);
    }
    fputcsv($output, []);
    
    // Revenue by Employee
    fputcsv($output, ['Revenue by Employee']);
    fputcsv($output, ['Employee', 'Appointments', 'Revenue']);
    foreach ($employee_revenue as $row) {
        fputcsv($output, [
            $row['name'], 
            $row['appointment_count'], 
            'BDT ' . number_format($row['revenue'], 2)
        ]);
    }
    fputcsv($output, []);
    
    // Payment Methods
    fputcsv($output, ['Payment Methods']);
    fputcsv($output, ['Method', 'Count', 'Total']);
    foreach ($payment_methods as $row) {
        fputcsv($output, [
            $row['payment_method'], 
            $row['count'], 
            'BDT ' . number_format($row['total'], 2)
        ]);
    }
    fputcsv($output, []);
    
    // Monthly Revenue Trend
    fputcsv($output, ['Monthly Revenue Trend - ' . $current_year]);
    fputcsv($output, ['Month', 'Revenue']);
    foreach ($all_months as $row) {
        fputcsv($output, [
            $row['month'], 
            'BDT ' . number_format($row['revenue'], 2)
        ]);
    }
    
    fclose($output);
    exit;

} elseif ($type === 'pdf') {
    class PDF extends FPDF {
        private $brandColor = [106, 17, 203]; // Purple brand color
        private $currency = 'BDT ';  // Use BDT instead of Taka symbol

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

        function SummaryBoxes($revenue, $appointments, $new_customers = 0) {
            // First row of summary boxes
            $this->SetFillColor(248, 249, 250);
            $this->SetDrawColor(222, 226, 230);
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(95, 8, 'TOTAL REVENUE', 'TLR', 0, 'C', true);
            $this->Cell(10, 8, '', 0, 0);
            $this->Cell(85, 8, 'COMPLETED APPOINTMENTS', 'TLR', 1, 'C', true);
            $this->SetFont('Arial', 'B', 20);
            $this->Cell(95, 15, $this->currency . number_format($revenue, 2), 'BLR', 0, 'C');
            $this->Cell(10, 15, '', 0, 0);
            $this->Cell(85, 15, $appointments, 'BLR', 1, 'C');
            
            // Second row - New customers
            $this->Ln(5);
            $this->SetFont('Arial', 'B', 10);
            $this->Cell(95, 8, 'NEW CUSTOMERS', 'TLR', 0, 'C', true);
            $this->Cell(10, 8, '', 0, 0);
            $this->Cell(85, 8, 'GENERATED ON', 'TLR', 1, 'C', true);
            $this->SetFont('Arial', 'B', 20);
            $this->Cell(95, 15, $new_customers, 'BLR', 0, 'C');
            $this->Cell(10, 15, '', 0, 0);
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(85, 15, date('d M Y, h:i A'), 'BLR', 1, 'C');
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
                
                // Use BDT instead of Taka symbol
                if(is_numeric($row[2])) {
                    $value = $this->currency . number_format($row[2], 2);
                } else {
                    $value = $row[2];
                }
                $this->Cell($w[2], 6, $value, 'LR', 0, 'R', $fill);
                
                $this->Ln();
                $fill = !$fill;
            }
            $this->Cell(array_sum($w), 0, '', 'T');
            $this->Ln(10);
        }

        // NEW: Payment methods table with a different format
        function PaymentMethodsTable($data) {
            $this->SetFont('Arial', 'B', 12);
            $this->SetTextColor($this->brandColor[0], $this->brandColor[1], $this->brandColor[2]);
            $this->Cell(0, 10, 'Payment Methods', 0, 1);
            $this->SetFillColor($this->brandColor[0], $this->brandColor[1], $this->brandColor[2]);
            $this->SetTextColor(255);
            $this->SetDrawColor(222, 226, 230);
            $this->SetFont('Arial', 'B', 10);
            
            // Header with different widths
            $w = [70, 50, 70];
            $this->Cell($w[0], 7, 'Payment Method', 1, 0, 'C', true);
            $this->Cell($w[1], 7, 'Count', 1, 0, 'C', true);
            $this->Cell($w[2], 7, 'Total Revenue', 1, 1, 'C', true);
            
            $this->SetFillColor(240, 240, 240);
            $this->SetTextColor(0);
            $this->SetFont('Arial', '', 10);
            $fill = false;
            
            $total_count = 0;
            $total_revenue = 0;
            
            foreach($data as $row) {
                // Capitalize payment method names
                $method = ucwords(str_replace('_', ' ', $row['payment_method']));
                $this->Cell($w[0], 6, $method, 'LR', 0, 'L', $fill);
                $this->Cell($w[1], 6, $row['count'], 'LR', 0, 'C', $fill);
                $this->Cell($w[2], 6, $this->currency . number_format($row['total'], 2), 'LR', 1, 'R', $fill);
                $fill = !$fill;
                
                $total_count += $row['count'];
                $total_revenue += $row['total'];
            }
            
            // Add total row
            $this->SetFont('Arial', 'B', 10);
            $this->Cell($w[0], 7, 'TOTAL', 'LRB', 0, 'L');
            $this->Cell($w[1], 7, $total_count, 'LRB', 0, 'C');
            $this->Cell($w[2], 7, $this->currency . number_format($total_revenue, 2), 'LRB', 1, 'R');
            
            $this->Ln(10);
        }
        
        // NEW: Monthly trend chart
        function MonthlyTrendTable($data, $year) {
            $this->SetFont('Arial', 'B', 12);
            $this->SetTextColor($this->brandColor[0], $this->brandColor[1], $this->brandColor[2]);
            $this->Cell(0, 10, 'Monthly Revenue Trend - ' . $year, 0, 1);
            $this->SetFillColor($this->brandColor[0], $this->brandColor[1], $this->brandColor[2]);
            $this->SetTextColor(255);
            $this->SetDrawColor(222, 226, 230);
            $this->SetFont('Arial', 'B', 10);
            
            // Header
            $w = [95, 95];
            $this->Cell($w[0], 7, 'Month', 1, 0, 'C', true);
            $this->Cell($w[1], 7, 'Revenue', 1, 1, 'C', true);
            
            $this->SetFillColor(240, 240, 240);
            $this->SetTextColor(0);
            $this->SetFont('Arial', '', 10);
            $fill = false;
            
            $total_revenue = 0;
            
            foreach($data as $row) {
                $this->Cell($w[0], 6, $row['month'], 'LR', 0, 'L', $fill);
                $this->Cell($w[1], 6, $this->currency . number_format($row['revenue'], 2), 'LR', 1, 'R', $fill);
                $fill = !$fill;
                
                $total_revenue += $row['revenue'];
            }
            
            // Add total row
            $this->SetFont('Arial', 'B', 10);
            $this->Cell($w[0], 7, 'YEARLY TOTAL', 'LRB', 0, 'L');
            $this->Cell($w[1], 7, $this->currency . number_format($total_revenue, 2), 'LRB', 1, 'R');
            
            $this->Ln(10);
        }
    }

    $pdf = new PDF('P', 'mm', 'A4');
    $pdf->AliasNbPages();
    $pdf->AddPage();
    
    $pdf->ReportTitle($start_date, $end_date);
    $pdf->SummaryBoxes($summary['total_revenue'], $summary['total_appointments'], $summary['new_customers']);

    // Service Revenue Table
    $header_service = ['Service', 'Appointments', 'Revenue'];
    $data_service = array_map(function($row) { return [$row['name'], $row['appointment_count'], $row['revenue']]; }, $service_revenue);
    $pdf->ReportTable('Revenue by Service', $header_service, $data_service);

    // Employee Revenue Table
    $header_employee = ['Employee', 'Appointments', 'Revenue'];
    $data_employee = array_map(function($row) { return [$row['name'], $row['appointment_count'], $row['revenue']]; }, $employee_revenue);
    $pdf->ReportTable('Revenue by Employee', $header_employee, $data_employee);
    
    // Payment Methods Table
    $pdf->PaymentMethodsTable($payment_methods);
    
    // Check if adding monthly trend would exceed page limit
    if($pdf->GetY() > 220) {
        $pdf->AddPage();
    }
    
    // Monthly Trend Table
    $pdf->MonthlyTrendTable($all_months, $current_year);

    $pdf->Output('D', "{$filename}.pdf");
    exit;

} else {
    // Invalid type
    http_response_code(400);
    echo "Invalid export type specified. Use 'csv' or 'pdf'.";
    exit;
}
?>