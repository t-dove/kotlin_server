<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'php_mailer/Exception.php';
require 'php_mailer/PHPMailer.php';
require 'php_mailer/SMTP.php';

function checkAccess($access_token, $code = null)
{
    global $database;
    if ($code == null) {
        $check = $database->sql_query("SELECT * FROM user WHERE access = ? AND code IS NULL", array($access_token))['result'][0];
        return $check['access'] == $access_token ? $check['id'] : false;
    } else {
        $check = $database->sql_query("SELECT * FROM user WHERE access = ? AND code = ?", array($access_token, $code))['result'][0];
        return $check['access'] == $access_token ? $check['id'] : false;
    }
}

function getToken($length)
{
    $lit = [
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', '-', 'm', 'n', 'o', 'p', 'q', 'r',
        's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '-'
    ];
    $counted = 1;
    $token111 = "";
    while ($counted <= $length) {
        $type = rand(1, 2);
        if ($type == 1) {
            $rand_s = $lit[array_rand($lit)];
            $token111 = "{$token111}{$rand_s}";
        } else if ($type == 2) {
            $rand_s = rand(0, 9);
            $token111 = "{$token111}{$rand_s}";
        }
        $counted++;
    }
    return $token111;
}


function pingUser($user_id, $data)
{
    global $database;
    $user = new User($user_id);
    $fcm_token = $user->getFcmToken();
    $fcm_api = new FCMNotifier();
    $result = $fcm_api->sendNotification($fcm_token, $data);
    return true;
}
function createChat($first_user, $second_user)
{
    global $database;
    $unic = false;
    while (!$unic) {
        $token = getToken(25);
        $get_token = $database->sql_query("SELECT * FROM chat WHERE chat_token = ?", array($token))['result'][0];
        if ($get_token['chat_token'] != $token) {
            $unic = true;
        }
    }
    $create_chat = $database->sql_query("INSERT INTO `chat`(`chat_token`,`first_user`,`second_user`) VALUES (?,?,?)", array($token, $first_user, $second_user), true)['insert_id'];
    return $token;
}

function setAnswer($info)
{
    $result['response'] = $info;
    return json_encode($result);
}

function checkRegistration($email)
{
    global $database;
    $check = $database->sql_query("SELECT * FROM user WHERE email = ?", array($email))['result'][0];
    return $check['email'] == $email ? $check['id'] : false;
}


function registrate($email, $fcm_token)
{
    global $database;
    $reg_user = $database->sql_query("INSERT INTO `user`(`email`) VALUES (?)", array($email), true)['insert_id'];
    $user_obj = new User($reg_user);
    $token = $user_obj->setAccessToken($fcm_token);
    return $token;
}

function send_email($alt_body,$body,$subject, $to)
{
    $mail = new PHPMailer(true);
    try {
        // Settings
        $mail->isSMTP();                                            //Send using SMTP
        $mail->Host       = 'cr-house.ru';                     //Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
        $mail->Username   = 'notify@cr-house.ru';                     //SMTP username
        $mail->Password   = 'jNo7EsqGqF0ZnYgt';                               //SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;            //Enable implicit TLS encryption
        $mail->Port       = 587;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
        $mail->CharSet = "UTF-8";
        // Content
        $mail->setFrom('notify@cr-house.ru');
        $mail->FromName = "CyberHouse";
        $mail->addAddress($to);
        $mail->isHTML(true);                       // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $alt_body;

        $mail->DKIM_domain = 'cr-house.ru';
        $mail->DKIM_private = '/var/www/cr_house_ru_usr/data/key-private.pem';
        $mail->DKIM_selector = 'mail';

        $mail->send();
        return true;
    } catch (Exception $e) {
        return ($mail->ErrorInfo); //echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
