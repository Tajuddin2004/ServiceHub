<?php

define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_USER',     'team110.servicehub@gmail.com');   
define('SMTP_PASS',     'xiqn aiwt somu ooro'); 
define('SMTP_FROM',     'team110.servicehub@gmail.com');   
define('SMTP_FROM_NAME','Service-Hub');

require_once dirname(__DIR__) . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Sends an OTP email to the given address.
 *
 * @param string $toEmail   
 * @param string $toName    
 * @param string $otp      
 * @return bool             
 */
function sendOtpEmail(string $toEmail, string $toName, string $otp): bool
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Service-Hub Verification Code';
        $mail->Body    = getOtpEmailTemplate($toName, $otp);
        $mail->AltBody = "Hi {$toName},\n\nYour Service-Hub verification code is: {$otp}\n\nThis code expires in 10 minutes.\n\n— Service-Hub Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}


function getOtpEmailTemplate(string $name, string $otp): string
{
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Email Verification</title>
</head>
<body style="margin:0;padding:0;font-family:'Segoe UI',Arial,sans-serif;background:#0a0a0f;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#0a0a0f;padding:40px 20px;">
    <tr>
      <td align="center">
        <table width="560" cellpadding="0" cellspacing="0" style="background:linear-gradient(135deg,#1a1a2e,#16213e);border-radius:20px;border:1px solid rgba(255,255,255,0.1);overflow:hidden;max-width:100%;">
          <!-- Header -->
          <tr>
            <td style="background:linear-gradient(90deg,#6366f1,#a855f7,#22d3ee);padding:4px 0;"></td>
          </tr>
          <!-- Logo -->
          <tr>
            <td align="center" style="padding:40px 40px 20px;">
              <img src="http://localhost/service-hub/assets/images/icon.jpeg" width="60" height="60" alt="Service-Hub" style="border-radius:12px;display:block;margin:0 auto 10px;">
              <div style="font-size:1.6rem;font-weight:700;background:linear-gradient(135deg,#22d3ee,#a855f7);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">Service-Hub</div>
            </td>
          </tr>
          <!-- Body -->
          <tr>
            <td style="padding:0 40px 20px;color:#ffffff;text-align:center;">
              <h2 style="font-size:1.4rem;font-weight:600;margin:0 0 10px;">Verify Your Email Address</h2>
              <p style="color:rgba(255,255,255,0.65);margin:0 0 30px;line-height:1.6;">Hi <strong style="color:#fff;">{$name}</strong>, use the code below to complete your registration. It expires in <strong style="color:#22d3ee;">10 minutes</strong>.</p>

              <!-- OTP Box -->
              <div style="background:rgba(99,102,241,0.15);border:2px solid rgba(99,102,241,0.4);border-radius:16px;padding:28px 20px;margin:0 auto 30px;display:inline-block;width:100%;max-width:320px;box-sizing:border-box;">
                <div style="letter-spacing:14px;font-size:2.4rem;font-weight:800;color:#ffffff;text-align:center;">{$otp}</div>
                <div style="color:rgba(255,255,255,0.5);font-size:0.78rem;margin-top:10px;">VERIFICATION CODE</div>
              </div>

              <p style="color:rgba(255,255,255,0.45);font-size:0.85rem;margin:0 0 10px;">If you didn't create an account with Service-Hub, please ignore this email.</p>
            </td>
          </tr>
          <!-- Footer -->
          <tr>
            <td style="padding:20px 40px 35px;text-align:center;border-top:1px solid rgba(255,255,255,0.07);">
              <p style="color:rgba(255,255,255,0.3);font-size:0.78rem;margin:0;">© 2025 Service-Hub. All rights reserved.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
}
