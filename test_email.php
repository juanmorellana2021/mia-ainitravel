<?php
require_once '/var/www/html/manage/vendor/phpmailer/phpmailer/src/Exception.php';
require_once '/var/www/html/manage/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once '/var/www/html/manage/vendor/phpmailer/phpmailer/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
$mail = new PHPMailer(true);
try {
    $mail->SMTPDebug = 2;
    $mail->isSMTP();
    $mail->Host = 'smtp.ionos.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'support@mia-whatsapp.com';
    $mail->Password = 'JU{or}0027';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom('support@mia-whatsapp.com', 'Mia WhatsApp');
    $mail->addAddress('juanorellanawork2018@gmail.com');
    $mail->isHTML(true);
    $mail->Subject = 'Test Email - Mia WhatsApp';
    $mail->Body = '<h2>Test from Mia</h2><p>If you see this in spam, we need to fix DNS.</p><p>Sent: ' . date('Y-m-d H:i:s') . '</p>';
    $mail->send();
    echo "SUCCESS: Email sent\n";
} catch (Exception $e) {
    echo "FAILED: " . $mail->ErrorInfo . "\n";
}
