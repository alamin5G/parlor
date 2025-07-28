<?php
// Load PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Send an email using PHPMailer and the configured SMTP settings
 * 
 * @param string $to_email Recipient email address
 * @param string $to_name Recipient name
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $alt_body Plain text alternative (optional)
 * @return array ['success' => bool, 'message' => string]
 */
function send_email($to_email, $to_name, $subject, $body, $alt_body = '') {
    // Load SMTP configuration
    $smtp_config = require_once __DIR__ . '/../config/smtp_config.php';
    
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $smtp_config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_config['username'];
        $mail->Password   = $smtp_config['password'];
        $mail->SMTPSecure = $smtp_config['encryption'];
        $mail->Port       = $smtp_config['port'];
        
        // Recipients
        $mail->setFrom($smtp_config['from_email'], $smtp_config['from_name']);
        $mail->addAddress($to_email, $to_name);
        $mail->addReplyTo($smtp_config['reply_to'], $smtp_config['from_name']);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        if (!empty($alt_body)) {
            $mail->AltBody = $alt_body;
        } else {
            $mail->AltBody = strip_tags($body);
        }
        
        $mail->send();
        return [
            'success' => true,
            'message' => 'Email sent successfully'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => "Email could not be sent. Mailer Error: {$mail->ErrorInfo}"
        ];
    }
}
?>