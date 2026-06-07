<?php
/**
 * Rai by Rai - Submit Inquiry Endpoint
 */

header('Content-Type: application/json');

// Include configuration and database connection
require_once 'config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.'
    ]);
    exit;
}

// ─── READ AND SANITIZE INPUTS ───
$name     = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
$email    = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$phone    = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS);
$location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_SPECIAL_CHARS);
$package  = filter_input(INPUT_POST, 'package', FILTER_SANITIZE_SPECIAL_CHARS);
$budget   = filter_input(INPUT_POST, 'budget', FILTER_SANITIZE_SPECIAL_CHARS);
$message  = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_SPECIAL_CHARS);

// Validate required fields
if (!$name || !$email || !$phone || !$location || !$package || !$budget) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please fill in all required fields with valid details.'
    ]);
    exit;
}

// ─── INSERT INTO DATABASE ───
try {
    $stmt = $pdo->prepare("
        INSERT INTO inquiries (name, email, phone, location, package, budget, message, status)
        VALUES (:name, :email, :phone, :location, :package, :budget, :message, 'New')
    ");
    
    $stmt->execute([
        ':name'     => $name,
        ':email'    => $email,
        ':phone'    => $phone,
        ':location' => $location,
        ':package'  => $package,
        ':budget'   => $budget,
        ':message'  => $message ? $message : 'No message provided.'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: Unable to store your inquiry.'
    ]);
    exit;
}

// ─── SEND EMAIL CONFIRMATIONS ───
// Reading email templates
$customer_template = @file_get_contents('emails/customer_welcome.html');
$admin_template = @file_get_contents('emails/admin_notification.html');

// Fallback plain-text templates if HTML files are missing
if (!$customer_template) {
    $customer_template = "Thank you {{name}} for contacting Rai by Rai. We received your request for {{package}}.";
}
if (!$admin_template) {
    $admin_template = "New inquiry from {{name}}\nEmail: {{email}}\nPhone: {{phone}}\nPackage: {{package}}\nBudget: {{budget}}\nLocation: {{location}}\nMessage: {{message}}";
}

// Replace placeholders
$placeholders = [
    '{{name}}'     => $name,
    '{{email}}'    => $email,
    '{{phone}}'    => $phone,
    '{{location}}' => $location,
    '{{package}}'  => $package,
    '{{budget}}'   => $budget,
    '{{message}}'  => nl2br($message ? $message : 'No message provided.')
];

$customer_html = str_replace(array_keys($placeholders), array_values($placeholders), $customer_template);
$admin_html = str_replace(array_keys($placeholders), array_values($placeholders), $admin_template);

// Send welcome email to customer
$customer_mail_sent = send_mail_helper(
    $email,
    "We have received your inquiry - Rai by Rai",
    $customer_html,
    SMTP_FROM,
    SMTP_FROM_NAME
);

// Send notification email to admin
$admin_mail_sent = send_mail_helper(
    ADMIN_EMAIL,
    "New Lead: {$name} - {$package}",
    $admin_html,
    SMTP_FROM,
    SMTP_FROM_NAME
);

// Return successful response
echo json_encode([
    'status' => 'success',
    'message' => 'Your inquiry has been submitted successfully! We will connect with you shortly.'
]);
exit;


/**
 * Helper function to send email via SMTP or fallback to PHP mail()
 */
function send_mail_helper($to, $subject, $body_html, $from_email, $from_name) {
    // 1. If SMTP config is fully set, attempt custom SMTP socket dispatch
    if (defined('SMTP_PASS') && SMTP_PASS !== '') {
        $smtp_sent = send_smtp_socket($to, $subject, $body_html, $from_email, $from_name);
        if ($smtp_sent) {
            return true;
        }
    }
    
    // 2. Fallback to standard PHP mail()
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=utf-8\r\n";
    $headers .= "From: =?UTF-8?B?" . base64_encode($from_name) . "?= <{$from_email}>\r\n";
    $headers .= "Reply-To: {$from_email}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    return @mail($to, $subject, $body_html, $headers);
}

/**
 * Custom socket SMTP client in pure PHP for secure authenticated dispatch
 */
function send_smtp_socket($to, $subject, $body_html, $from_email, $from_name) {
    $host = SMTP_HOST;
    $port = SMTP_PORT;
    $user = SMTP_USER;
    $pass = SMTP_PASS;
    
    // Use TLS protocol suffix for port 465
    $remote = ($port == 465) ? "ssl://{$host}" : "tcp://{$host}";
    
    $socket = @fsockopen($remote, $port, $errno, $errstr, 10);
    if (!$socket) {
        return false;
    }
    
    // Read welcome message
    fgets($socket, 512);
    
    // HELO command
    fwrite($socket, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
    fgets($socket, 512);
    
    // If port 587, initiate STARTTLS
    if ($port == 587) {
        fwrite($socket, "STARTTLS\r\n");
        $res = fgets($socket, 512);
        if (strpos($res, '220') === false) {
            fclose($socket);
            return false;
        }
        
        // Enable encryption on the current stream
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return false;
        }
        
        // Re-identify after encryption
        fwrite($socket, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
        fgets($socket, 512);
    }
    
    // AUTH LOGIN
    fwrite($socket, "AUTH LOGIN\r\n");
    $res = fgets($socket, 512);
    if (strpos($res, '334') === false) {
        fclose($socket);
        return false;
    }
    
    // Username
    fwrite($socket, base64_encode($user) . "\r\n");
    fgets($socket, 512);
    
    // Password
    fwrite($socket, base64_encode($pass) . "\r\n");
    $res = fgets($socket, 512);
    if (strpos($res, '235') === false) {
        fclose($socket);
        return false;
    }
    
    // MAIL FROM
    fwrite($socket, "MAIL FROM: <{$from_email}>\r\n");
    fgets($socket, 512);
    
    // RCPT TO
    fwrite($socket, "RCPT TO: <{$to}>\r\n");
    fgets($socket, 512);
    
    // DATA
    fwrite($socket, "DATA\r\n");
    fgets($socket, 512);
    
    // Headers & Message Body
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=utf-8\r\n";
    $headers .= "To: {$to}\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "From: =?UTF-8?B?" . base64_encode($from_name) . "?= <{$from_email}>\r\n";
    $headers .= "Reply-To: {$from_email}\r\n";
    
    fwrite($socket, $headers . "\r\n" . $body_html . "\r\n.\r\n");
    fgets($socket, 512);
    
    // QUIT
    fwrite($socket, "QUIT\r\n");
    fclose($socket);
    
    return true;
}
