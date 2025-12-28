<?php
// modules/operational_projects/notification_helper.php

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../modules/todos/todo_functions.php';

// استدعاء PHPMailer
if (file_exists(__DIR__ . '/../../core/PHPMailer/src/Exception.php')) {
    require_once __DIR__ . '/../../core/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../../core/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../../core/PHPMailer/src/SMTP.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendProjectNotification($user_id, $title, $message, $entity_type, $entity_id) {
    $db = Database::getInstance()->pdo();

    // 1. إنشاء المهمة في النظام
    addSystemTodo($user_id, $title, $message, $entity_type, $entity_id, date('Y-m-d'));

    // 2. تجهيز الرابط (تأكد أن BASE_URL في config.php يبدأ بـ http://)
    $actionUrl = BASE_URL; 
    $btnText = "View Details";

    switch ($entity_type) {
        case 'project_view':
            $actionUrl .= "modules/operational_projects/view.php?id=" . $entity_id;
            $btnText = "Open Project";
            break;
        case 'project_update':
            $actionUrl .= "modules/operational_projects/updates_reminder.php?id=" . $entity_id;
            $btnText = "Submit Update";
            break;
        case 'task_view':
            $pid = $db->query("SELECT project_id FROM project_tasks WHERE id = $entity_id")->fetchColumn();
            $actionUrl .= "modules/operational_projects/milestones.php?id=" . $pid; 
            $btnText = "View Task";
            break;
        case 'project_approvals':
            $actionUrl .= "modules/operational_projects/approvals.php?id=" . $entity_id;
            $btnText = "Check Approvals";
            break;
        case 'collaboration_review':
            $actionUrl .= "modules/collaborations/index.php";
            $btnText = "Review Request";
            break;
        case 'kpi_view':
            $actionUrl .= "modules/operational_projects/kpis.php?id=" . $entity_id;
            $btnText = "View KPIs";
            break;
        case 'kpi_view_direct':
            $pid = $db->query("SELECT parent_id FROM kpis WHERE id = $entity_id")->fetchColumn();
            $actionUrl .= "modules/operational_projects/kpis.php?id=" . $pid;
            $btnText = "Update Reading";
            break;    
        default:
            $actionUrl .= "index.php"; 
            break;
    }

    // 3. جلب بيانات المستخدم
    $stmt = $db->prepare("SELECT email, full_name_en FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user || empty($user['email'])) {
        return;
    }

    // 4. إرسال الإيميل
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8'; 

            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($user['email'], $user['full_name_en']);

            $mail->isHTML(true);
            $mail->Subject = "PMS Notification: " . $title;
            
            $currentYear = date('Y');
            
            // --- القالب المحدث (Table-Based Layout for Outlook Compatibility) ---
            $mailBody = "
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <link rel='preconnect' href='https://fonts.googleapis.com'>
                <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
                <link href='https://fonts.googleapis.com/css2?family=Fira+Sans+Condensed:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Harmattan:wght@400;500;600;700&display=swap' rel='stylesheet'>
                <link rel='preconnect' href='https://fonts.googleapis.com'>
                <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
                <link href='https://fonts.googleapis.com/css2?family=Fira+Sans+Condensed:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Harmattan:wght@400;500;600;700&family=Varela+Round&display=swap' rel='stylesheet'>
                
                <style>
                    body { margin: 0; padding: 0; background-color: #f4f4f4; font-family: varela round, sans-serif; }
                    table { border-collapse: collapse; }
                    a { text-decoration: none; }
                </style>
            </head>
            <body style='margin: 0; padding: 0; background-color: #f4f4f4;'>
                <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                    <tr>
                        <td align='center' style='padding: 40px 0;'>
                            <table border='0' cellpadding='0' cellspacing='0' width='600' style='background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05);'>
                                <tr>
                                    <td bgcolor='#ff8c00' height='6' style='font-size: 0; line-height: 0;'>&nbsp;</td>
                                </tr>
                                
                                <tr>
                                    <td style='padding: 40px;'>
                                        <h1 style='margin: 0 0 20px 0; font-size: 22px; color: #2d3436; font-weight: 700; font-family: varela round, sans-serif;'>$title</h1>
                                        
                                        <p style='margin: 0 0 25px 0; font-size: 16px; color: #555555; line-height: 1.6; font-family: varela round, sans-serif;'>
                                            Hello <strong>{$user['full_name_en']}</strong>,<br><br>
                                            $message
                                        </p>

                                        <table border='0' cellspacing='0' cellpadding='0'>
                                            <tr>
                                                <td align='center' bgcolor='#ff8c00' style='border-radius: 50px;'>
                                                    <a href='$actionUrl' target='_blank' style='font-size: 16px; font-family: varela round, sans-serif; color: #ffffff; text-decoration: none; border-radius: 50px; padding: 12px 30px; border: 1px solid #ff8c00; display: inline-block; font-weight: bold;'>
                                                        $btnText
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                        
                                        <br>
                                        <p style='margin-top: 30px; font-size: 12px; color: #999; text-align: left;'>
                                            If the button doesn't work, copy this link:<br>
                                            <a href='$actionUrl' style='color: #ff8c00;'>$actionUrl</a>
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <td align='center' style='padding: 20px; background-color: #f9f9f9; border-top: 1px solid #eeeeee;'>
                                        <p style='margin: 0; font-size: 12px; color: #999999; font-family: varela round, sans-serif;'>
                                            &copy; $currentYear Project Management System.<br>
                                            Al Yamamah University
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>
            ";
            
            $mail->Body = $mailBody;
            $mail->AltBody = strip_tags($message) . "\n\nLink: $actionUrl";

            $mail->send();
            
        } catch (Exception $e) {
            error_log("Mailer Error: " . $mail->ErrorInfo);
        }
    } else {
        // Fallback
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: ' . SMTP_FROM_EMAIL . "\r\n";
        $msgWithLink = "$message <br><br> <a href='$actionUrl'>$btnText</a>";
        @mail($user['email'], "PMS Alert: " . $title, $msgWithLink, $headers);
    }
}
?>