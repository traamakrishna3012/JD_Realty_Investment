<?php
/**
 * Email Configuration for JD Realty
 * Using SMTP for reliable email delivery - Configured for Bigrock Server
 */

// SMTP Configuration for Bigrock
define('SMTP_HOST', 'mail.jdrealtyinvestment.com');  // Bigrock mail server
define('SMTP_PORT', 465);                             // SSL port (or 587 for TLS)
define('SMTP_USERNAME', 'info@jdrealtyinvestment.com');  // Email you created in cPanel
define('SMTP_PASSWORD', 'Dinesh@12345');  // Password you set in cPanel
define('SMTP_ENCRYPTION', 'ssl');                     // 'ssl' for port 465, 'tls' for port 587

// Email addresses
define('ADMIN_EMAIL', 'info@jdrealtyinvestment.com');  // Where inquiries go
define('FROM_EMAIL', 'info@jdrealtyinvestment.com'); // Sender email
define('FROM_NAME', 'JD Realty & Investment');

/**
 * Send email using PHPMailer with Bigrock SMTP
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $html_body HTML email body
 * @param string $reply_to Reply-to email (optional)
 * @return bool Success status
 */
function sendEmail($to, $subject, $html_body, $reply_to = null) {
    // PHPMailer path
    $phpmailer_path = dirname(__FILE__) . '/../phpmailer/PHPMailer.php';
    
    if (file_exists($phpmailer_path)) {
        require_once dirname(__FILE__) . '/../phpmailer/PHPMailer.php';
        require_once dirname(__FILE__) . '/../phpmailer/SMTP.php';
        require_once dirname(__FILE__) . '/../phpmailer/Exception.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // SMTP settings for Bigrock
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; // SSL
            $mail->Port = SMTP_PORT;
            
            // Bigrock specific settings
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // Timeout settings
            $mail->Timeout = 30;
            $mail->SMTPKeepAlive = true;
            
            // Recipients
            $mail->setFrom(FROM_EMAIL, FROM_NAME);
            $mail->addAddress($to);
            
            if ($reply_to) {
                $mail->addReplyTo($reply_to);
            }
            
            // Content
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body = $html_body;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html_body));
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
            return false;
        }
    }
    
    // Fallback to PHP mail() function (may not work on all servers)
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
    if ($reply_to) {
        $headers .= "Reply-To: " . $reply_to . "\r\n";
    }
    
    return @mail($to, $subject, $html_body, $headers);
}
?>
