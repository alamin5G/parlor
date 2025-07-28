<?php
// SMTP Configuration Settings
return [
    'host' => 'smtp.gmail.com',           // SMTP server (e.g., Gmail)
    'port' => 587,                        // SMTP port (587 for TLS, 465 for SSL)
    'username' => 'youremail@gmail.com',  // Your email address
    'password' => 'your-app-password',    // Your app password (for Gmail)
    'encryption' => 'tls',                // 'tls' or 'ssl'
    'from_email' => 'youremail@gmail.com',
    'from_name' => 'Aura Salon & Spa',
    'reply_to' => 'noreply@aurasalon.com'
];
?>