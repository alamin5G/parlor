<?php
// Load PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer's autoloader
require_once $_SERVER['DOCUMENT_ROOT'] . '/parlor/vendor/autoload.php';

/**
 * Sends an email using PHPMailer with SMTP.
 *
 * @param string $to_email The recipient's email address.
 * @param string $to_name The recipient's name.
 * @param string $subject The email subject.
 * @param string $body The email body (HTML).
 * @return array ['success' => bool, 'message' => string]
 */
function send_email($to_email, $to_name, $subject, $body) {
    $mail = new PHPMailer(true);
    $config = require 'email_conf.php';

    try {
        // Server settings
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Uncomment for detailed error output
        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        
        // --- FIX: Use the encryption setting from the config file ---
        if ($config['encryption'] === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }
        
        $mail->Port       = $config['port'];

        // Recipients
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($to_email, $to_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully.'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"];
    }
}


function send_bill_receipt_email($to_email, $to_name, $pdf_path, $bill_id) {
    $mail = new PHPMailer(true);
    $config = require 'email_conf.php';
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        
        // Use the encryption setting from the config file
        if ($config['encryption'] === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }
        
        $mail->Port = $config['port'];

        // Recipients
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($to_email, $to_name);

        $mail->Subject = "Your Receipt - Bill #$bill_id";
        $mail->isHTML(true);
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background-color: #f5f5f5; padding: 20px; text-align: center;'>
                    <h2 style='color: #333;'>Payment Receipt</h2>
                </div>
                <div style='padding: 20px;'>
                    <p>Dear $to_name,</p>
                    <p>Thank you for your payment. Your receipt (Bill #$bill_id) is attached to this email.</p>
                    <p>We look forward to seeing you again soon!</p>
                    <p>Best regards,<br>Labonno Glamour World Team</p>
                </div>
                <div style='background-color: #f5f5f5; padding: 10px; text-align: center; font-size: 12px; color: #666;'>
                    <p>This is an automated message. Please do not reply directly to this email.</p>
                </div>
            </div>
        ";
        $mail->AltBody = "Dear $to_name,\n\nThank you for your payment. Please find your receipt (Bill #$bill_id) attached.\n\nBest regards,\nLabonno Glamour World Team";
        $mail->addAttachment($pdf_path, 'receipt.pdf');

        $mail->send();
        return ['success' => true, 'message' => 'Receipt email sent successfully.'];
    } catch (Exception $e) {
        error_log("Email error: " . $mail->ErrorInfo);
        return ['success' => false, 'message' => "Receipt email could not be sent. Error: {$mail->ErrorInfo}"];
    }
}


/**
 * Send an appointment status update notification to the customer
 */
function send_appointment_status_email($customer_email, $customer_name, $appointment_id, $service_name, $scheduled_at, $new_status, $notes = '') {
    // Format the date and time
    $formatted_date = date('l, F j, Y', strtotime($scheduled_at));
    $formatted_time = date('h:i A', strtotime($scheduled_at));
    
    // Create status-specific messages
    $status_message = '';
    $subject = '';
    
    switch ($new_status) {
        case 'booked':
            $subject = "Your Appointment #$appointment_id Has Been Confirmed";
            $status_message = "<p style='font-weight: bold; color: #28a745;'>Your appointment has been confirmed!</p>
                               <p>We look forward to seeing you on $formatted_date at $formatted_time.</p>";
            break;
            
        case 'cancelled':
            $subject = "Your Appointment #$appointment_id Has Been Cancelled";
            $status_message = "<p style='font-weight: bold; color: #dc3545;'>Your appointment has been cancelled.</p>
                               <p>If you didn't request this cancellation, please contact us immediately.</p>";
            break;
            
        case 'completed':
            $subject = "Thank You for Visiting Labonno Glamour World";
            $status_message = "<p style='font-weight: bold; color: #28a745;'>Thank you for visiting us!</p>
                               <p>We hope you enjoyed your service. We'd love to hear your feedback!</p>";
            break;
            
        case 'pending_payment':
            $subject = "Payment Required for Your Appointment #$appointment_id";
            $status_message = "<p style='font-weight: bold; color: #ffc107;'>Your appointment requires payment.</p>
                               <p>Please complete your payment to confirm your appointment slot.</p>
                               <p><a href='http://localhost/parlor/user/pay_online.php?appointment_id=$appointment_id' 
                                    style='background-color: #ffc107; color: #000; padding: 8px 15px; text-decoration: none; border-radius: 4px;'>
                                    Pay Now</a></p>";
            break;
            
        case 'rescheduled':
            $subject = "Your Appointment #$appointment_id Has Been Rescheduled";
            $status_message = "<p style='font-weight: bold; color: #17a2b8;'>Your appointment has been rescheduled.</p>
                               <p>Your new appointment time is $formatted_date at $formatted_time.</p>";
            break;
            
        default:
            $subject = "Update on Your Appointment #$appointment_id";
            $status_message = "<p>Your appointment status has been updated to: " . ucfirst($new_status) . "</p>";
    }
    
    // Add notes if provided
    $notes_html = '';
    if (!empty($notes)) {
        $notes_html = "<div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #6c757d; margin: 20px 0;'>
                          <h4 style='margin-top: 0;'>Notes:</h4>
                          <p>" . nl2br(htmlspecialchars($notes)) . "</p>
                       </div>";
    }
    
    // Build the email body
    $body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background-color: #f5f5f5; padding: 20px; text-align: center;'>
                <h2 style='color: #333;'>Appointment Update</h2>
            </div>
            <div style='padding: 20px;'>
                <p>Dear $customer_name,</p>
                
                $status_message
                
                <div style='margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px;'>
                    <h3 style='margin-top: 0;'>Appointment Details:</h3>
                    <p><strong>Service:</strong> $service_name</p>
                    <p><strong>Date:</strong> $formatted_date</p>
                    <p><strong>Time:</strong> $formatted_time</p>
                    <p><strong>Status:</strong> " . ucfirst($new_status) . "</p>
                </div>
                
                $notes_html
                
                <p>If you have any questions, please don't hesitate to contact us.</p>
                <p>Best regards,<br>Labonno Glamour World Team</p>
            </div>
            <div style='background-color: #f5f5f5; padding: 10px; text-align: center; font-size: 12px; color: #666;'>
                <p>This is an automated message. Please do not reply directly to this email.</p>
            </div>
        </div>
    ";
    
    // Send the email
    return send_email($customer_email, $customer_name, $subject, $body);
}

/**
 * Send payment status update notification to the customer
 */
function send_payment_status_email($customer_email, $customer_name, $payment_id, $amount, $method, $transaction_id, $new_status, $appointment_id, $service_name, $scheduled_at, $rejection_note = '') {
    // Format the date and time
    $formatted_date = date('l, F j, Y', strtotime($scheduled_at));
    $formatted_time = date('h:i A', strtotime($scheduled_at));
    
    // Create status-specific messages
    $status_message = '';
    $subject = '';
    
    switch ($new_status) {
        case 'approved':
            $subject = "Payment Approved for Your Appointment #$appointment_id";
            $status_message = "<p style='font-weight: bold; color: #28a745;'>Your payment has been approved!</p>
                               <p>Your appointment for $service_name on $formatted_date at $formatted_time is now confirmed.</p>";
            break;
            
        case 'rejected':
            $subject = "Payment Rejected for Your Appointment #$appointment_id";
            $status_message = "<p style='font-weight: bold; color: #dc3545;'>Your payment could not be verified.</p>
                               <p>Please submit a new payment to secure your appointment.</p>
                               <p><a href='http://localhost/parlor/user/pay_online.php?appointment_id=$appointment_id' 
                                    style='background-color: #dc3545; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px;'>
                                    Try Again</a></p>";
            break;
            
        default:
            $subject = "Update on Your Payment #$payment_id";
            $status_message = "<p>Your payment status has been updated to: " . ucfirst($new_status) . "</p>";
    }
    
    // Add rejection note if provided
    $notes_html = '';
    if ($new_status == 'rejected' && !empty($rejection_note)) {
        $notes_html = "<div style='background-color: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 20px 0;'>
                          <h4 style='margin-top: 0; color: #721c24;'>Reason for Rejection:</h4>
                          <p>" . nl2br(htmlspecialchars($rejection_note)) . "</p>
                       </div>";
    }
    
    // Build the email body
    $body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background-color: #f5f5f5; padding: 20px; text-align: center;'>
                <h2 style='color: #333;'>Payment Update</h2>
            </div>
            <div style='padding: 20px;'>
                <p>Dear $customer_name,</p>
                
                $status_message
                
                <div style='margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px;'>
                    <h3 style='margin-top: 0;'>Payment Details:</h3>
                    <p><strong>Amount:</strong> ৳" . number_format($amount, 2) . "</p>
                    <p><strong>Method:</strong> " . strtoupper($method) . "</p>
                    <p><strong>Transaction ID:</strong> $transaction_id</p>
                    <p><strong>Status:</strong> " . ucfirst($new_status) . "</p>
                </div>
                
                <div style='margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px;'>
                    <h3 style='margin-top: 0;'>Appointment Details:</h3>
                    <p><strong>Service:</strong> $service_name</p>
                    <p><strong>Date:</strong> $formatted_date</p>
                    <p><strong>Time:</strong> $formatted_time</p>
                </div>
                
                $notes_html
                
                <p>If you have any questions, please don't hesitate to contact us.</p>
                <p>Best regards,<br>Labonno Glamour World Team</p>
            </div>
            <div style='background-color: #f5f5f5; padding: 10px; text-align: center; font-size: 12px; color: #666;'>
                <p>This is an automated message. Please do not reply directly to this email.</p>
            </div>
        </div>
    ";
    
    // Send the email
    return send_email($customer_email, $customer_name, $subject, $body);
}

/**
 * Send a notification when a new appointment is created
 */
function send_new_appointment_email($customer_email, $customer_name, $appointment_id, $service_name, $scheduled_at, $beautician_name = null, $price = 0, $status = 'booked') {
    // Format the date and time
    $formatted_date = date('l, F j, Y', strtotime($scheduled_at));
    $formatted_time = date('h:i A', strtotime($scheduled_at));
    
    // Format price
    $formatted_price = number_format($price, 2);
    
    // Create status-specific message
    $status_message = '';
    $payment_section = '';
    
    if ($status == 'pending_payment') {
        $status_message = "<p style='font-weight: bold; color: #ffc107;'>Your appointment requires payment before it's confirmed.</p>";
        $payment_section = "
            <div style='background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;'>
                <h4 style='margin-top: 0; color: #856404;'>Payment Required</h4>
                <p>To confirm your appointment, please complete your payment of ৳$formatted_price.</p>
                <p><a href='http://localhost/parlor/user/pay_online.php?appointment_id=$appointment_id' 
                     style='background-color: #ffc107; color: #000; padding: 8px 15px; text-decoration: none; border-radius: 4px;'>
                     Pay Now</a></p>
            </div>
        ";
    } else {
        $status_message = "<p style='font-weight: bold; color: #28a745;'>Your appointment has been successfully booked!</p>";
        $payment_section = "
            <div style='margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px;'>
                <p><strong>Price:</strong> ৳$formatted_price</p>
            </div>
        ";
    }
    
    // Subject line
    $subject = "Your Appointment Confirmation #$appointment_id - Labonno Glamour World";
    
    // Build the email body
    $body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background-color: #f5f5f5; padding: 20px; text-align: center;'>
                <h2 style='color: #333;'>Appointment Confirmation</h2>
            </div>
            <div style='padding: 20px;'>
                <p>Dear $customer_name,</p>
                
                $status_message
                
                <div style='margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px;'>
                    <h3 style='margin-top: 0;'>Appointment Details:</h3>
                    <p><strong>Service:</strong> $service_name</p>
                    <p><strong>Date:</strong> $formatted_date</p>
                    <p><strong>Time:</strong> $formatted_time</p>
                    " . ($beautician_name ? "<p><strong>Beautician:</strong> $beautician_name</p>" : "") . "
                </div>
                
                $payment_section
                
                <p>If you need to reschedule or cancel your appointment, please do so at least 24 hours in advance.</p>
                <p>We look forward to seeing you!</p>
                <p>Best regards,<br>Labonno Glamour World Team</p>
            </div>
            <div style='background-color: #f5f5f5; padding: 10px; text-align: center; font-size: 12px; color: #666;'>
                <p>This is an automated message. Please do not reply directly to this email.</p>
            </div>
        </div>
    ";
    
    // Send the email
    return send_email($customer_email, $customer_name, $subject, $body);
}



?>
