<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Function to send email using PHPMailer
function sendEmail($to, $subject, $text_message, $html_message = '') {
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'farisha1497@gmail.com';
        $mail->Password   = 'fxva faho wjeg zgne';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';
        
        // SSL Settings
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Recipients
        $mail->setFrom('farisha1497@gmail.com', 'SpliceNoise');
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        
        // Set HTML body if provided, otherwise create one from text message
        if (!empty($html_message)) {
            $mail->Body = $html_message;
            $mail->AltBody = $text_message;
        } else {
            // Create HTML version of the message
            $mail->Body = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                <div style="background: linear-gradient(135deg, #005761, #3B9999); padding: 20px; border-radius: 10px 10px 0 0;">
                    <h1 style="color: #ffffff; margin: 0; text-align: center;">SpliceNoise</h1>
                </div>
                <div style="background: #ffffff; padding: 20px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <p style="color: #333333; font-size: 16px; line-height: 1.6;">Hello,</p>
                    ' . nl2br(htmlspecialchars($text_message)) . '
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; color: #666666;">
                        <p style="font-size: 12px;">This is an automated message, please do not reply.</p>
                    </div>
                </div>
            </div>';
            $mail->AltBody = $text_message;
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?> 