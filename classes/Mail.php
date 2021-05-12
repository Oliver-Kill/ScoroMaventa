<?php

namespace ScoroMaventa;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Mail
{

    /**
     * @param string $subject
     * @param string $body
     * @return void
     * @throws Exception
     */
    static function send(string $subject, string $body)
    {
        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->SMTPDebug = PHPMAILER_SMTP_DEBUG;                 // Enable verbose debug output
            PHPMAILER_USE_SMTP ? $mail->isSMTP() : $mail->isMail();  // Send using SMTP
            $mail->Timeout = 5;                                      // Set mail server connection timeout in seconds
            $mail->Host = PHPMAILER_SMTP_HOST;                       // Set the SMTP server to send through
            $mail->SMTPAuth = PHPMAILER_SMTP_AUTH;                   // Enable SMTP authentication
            $mail->Username = PHPMAILER_SMTP_USERNAME;               // SMTP username
            $mail->Password = PHPMAILER_SMTP_PASSWORD;               // SMTP password
            PHPMAILER_SMTP_ENCRYPTION_ENABLED ?                      // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
                $mail->SMTPSecure = PHPMAILER_SMTP_ENCRYPTION_TYPE
                : null;
            $mail->Port = PHPMAILER_SMTP_PORT;                       // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

            //Recipients
            $mail->setFrom(PHPMAILER_FROM_EMAIL, PHPMAILER_FROM_NAME);
            $mail->addAddress(PHPMAILER_TO_EMAIL, PHPMAILER_TO_NAME);

            //Content
            $mail->isHTML(true);                               //Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);

            debug("Attempting to send mail");
            $mail->send();
            debug("Mail sent to " . PHPMAILER_TO_EMAIL);
        } catch (Exception $e) {
            throw new Exception($mail->ErrorInfo);
        }
    }
}