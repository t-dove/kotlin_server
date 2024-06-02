<?php

header('Access-Control-Allow-Origin: *');
include "fcm_api.php";
include "database.php"; // работа с БД
$database = new base();
include "main_func.php"; // основные функции
include "user.php"; // пользователь

$method_name = mb_strtolower($_POST['method']);

if ($_GET['method'] == "test") {
    try {
        $text = "awdlawd";
        $check_chat['next_msg'] = 16;
        $new_msg_data = ['type' => "new_message"];
        echo pingUser(20, $new_msgs_data);
    } catch (Exception $e) {
        echo $e;
    }
}

switch ($method_name) {
    case "auth":
        $email = $_POST['email'];
        $fcm_token = $_POST['fcm_token'];
        $user_check = checkRegistration($email);
        if ($user_check === false) {
            $reg = registrate($email, $fcm_token);
            $result['success'] = true;
            $result['auth_token'] = $reg;
            echo setAnswer($result);
        } else {
            $user = new User($user_check);
            $token = $user->setAccessToken($fcm_token);
            $result['success'] = true;
            $result['auth_token'] = $token;
            echo setAnswer($result);
        }
        break;
    case "confirm_code":
        $token = $_POST['auth_token'];
        $code = $_POST['code'];
        $check_access = checkAccess($token, $code);
        if ($check_access == false) {
            $result['success'] = false;
            echo setAnswer($result);
        } else {
            $user = new User($check_access);
            $user->confirmAuth();
            $result['success'] = true;
            echo setAnswer($result);
        }
        break;
    case "search_user":
        $token = $_POST['auth_token'];
        $check_access = checkAccess($token);
        if ($check_access == false) {
            $result['success'] = false;
            echo setAnswer($result);
        } else {
            $name = $_POST['name'];
            $self_age = $_POST['self_age'];
            $self_sex = $_POST['self_sex'];
            $search_age = $_POST['search_age'];
            $search_sex = $_POST['search_sex'];

            $user = new User($check_access);
            $search_chat = $user->searchChat($self_age, $self_sex, $search_age, $search_sex, $name);
            $result['success'] = true;
            $result = array_merge($result, $search_chat);
            echo setAnswer($result);
        }
        break;
    case "cancel_search":
        $token = $_POST['auth_token'];
        $check_access = checkAccess($token);
        if ($check_access == false) {
            $result['success'] = false;
            echo setAnswer($result);
        } else {
            $search_id = $_POST['search_id'];
            $user = new User($check_access);
            $search_chat = $user->cancelSearch($search_id);
            $result['success'] = true;
            echo setAnswer($result);
        }
        break;
    case "set_typing":
        $token = $_POST['auth_token'];
        $check_access = checkAccess($token);
        if ($check_access == false) {
            $result['success'] = false;
            echo setAnswer($result);
        } else {
            $chat_id = $_POST['chat_id'];
            $user = new User($check_access);
            $search_chat = $user->setTyping($chat_id);
            $result['success'] = true;
            echo setAnswer($result);
        }
        break;
    case "close_chat":
        $token = $_POST['auth_token'];
        $check_access = checkAccess($token);
        if ($check_access == false) {
            $result['success'] = false;
            echo setAnswer($result);
        } else {
            $chat_id = $_POST['chat_id'];
            $user = new User($check_access);
            $close_chat = $user->closeChat($chat_id);
            $result['success'] = true;
            echo setAnswer($result);
        }
        break;
    case "send_message":
        $token = $_POST['auth_token'];
        $check_access = checkAccess($token);
        if ($check_access == false) {
            $result['success'] = false;
            echo setAnswer($result);
        } else {
            $chat_id = $_POST['chat_id'];
            $text = $_POST['text'];
            $user = new User($check_access);
            $send_msg = $user->sendMessage($chat_id, $text);
            if ($send_msg === false) {
                $result['success'] = false;
                echo setAnswer($result);
            } else {
                $result['success'] = true;
                $result['msg_id'] = $send_msg;
                echo setAnswer($result);
            }
        }
        break;
    case "del_message":
        $token = $_POST['auth_token'];
        $check_access = checkAccess($token);
        if ($check_access == false) {
            $result['success'] = false;
            echo setAnswer($result);
        } else {
            $chat_id = $_POST['chat_id'];
            $msg_id = $_POST['msg_id'];
            $user = new User($check_access);
            $close_chat = $user->deleteMessage($chat_id, $msg_id);
            $result['success'] = true;
            echo setAnswer($result);
        }
        break;
}
