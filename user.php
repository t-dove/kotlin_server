<?php

class User
{
    public $user_id;
    function __construct(int $id)
    {
        $this->user_id = $id;
    }


    function setAccessToken($fcm_token)
    {
        $id = $this->user_id;
        global $database;
        $unic = false;
        while (!$unic) {
            $token = getToken(12);
            $get_token = $database->sql_query("SELECT * FROM user WHERE access = ?", array($token))['result'][0];
            if ($get_token['access'] != $token) {
                $unic = true;
            }
        }
        $auth_code = rand(100000, 999999);
        $set_token = $database->sql_query("UPDATE user SET access = ?, code=?, fcm_token=? WHERE id = ?", array($token, $auth_code, $fcm_token, $id));
        send_email($auth_code, "Для входа введите код: {$auth_code}", "Анонимный чат", $this->getEmail());
        return $token;
    }

    function confirmAuth()
    {
        $id = $this->user_id;
        global $database;
        $set_auth = $database->sql_query("UPDATE user SET code=NULL WHERE id = ?", array($id));
        return true;
    }

    function getEmail()
    {
        $id = $this->user_id;
        global $database;
        $check = $database->sql_query("SELECT * FROM user WHERE id = ?", array($id))['result'][0];
        return $check['email'];
    }

    function getFcmToken()
    {
        $id = $this->user_id;
        global $database;
        $check = $database->sql_query("SELECT * FROM user WHERE id = ?", array($id))['result'][0];
        return $check['fcm_token'];
    }

    function searchChat($self_age, $self_sex, $search_age, $search_sex, $name)
    {
        $id = $this->user_id;
        global $database;
        $search_ages_query_items = explode(",", $search_age);
        $search_ages_query = [];
        foreach ($search_ages_query_items as $item) {
            $search_ages_query[] = "?";
        }
        $search_ages_query = join(",", $search_ages_query);
        $search_sex_query = $search_sex == 3 ? "?,?" : "?";
        $search_sex_items = $search_sex == 3 ? [1, 2] : [$search_sex];

        $find_exist = $database->sql_query("SELECT * FROM chat_searching WHERE user_age IN($search_ages_query) AND user_sex IN($search_sex_query) AND FIND_IN_SET(?, search_age) AND (search_sex = ? OR search_sex = 3)", array_merge($search_ages_query_items, $search_sex_items, array($self_age, $self_sex)))['result'][0];
        if ($find_exist['id'] > 0) {
            $chat_with = $find_exist['user_id'];
            $set_chat = createChat($chat_with, $id);
            $database->sql_query("DELETE FROM chat_searching WHERE id = ?", array($find_exist['id']));
            $chat_data = ['type' => "chat_found", 'chat_id' => $set_chat, 'user_name' => $name, 'user_sex' => $self_sex];
            pingUser($chat_with, $chat_data);
            $return_data = ['chat_found' => true, 'chat_id' => $set_chat, 'user_name' => $find_exist['user_name'], 'user_sex' => $find_exist['user_sex']];
            return $return_data;
        } else {
            $create_search = $database->sql_query("INSERT INTO `chat_searching`(`user_name`, `user_id`, `user_age`, `user_sex`, `search_age`, `search_sex`) VALUES (?,?,?,?,?,?)", array($name, $id, $self_age, $self_sex, $search_age, $search_sex), true)['insert_id'];
            $return_data = ['chat_found' => false, 'search_id' => $create_search];
            return $return_data;
        }
    }

    function cancelSearch($search_id)
    {
        $id = $this->user_id;
        global $database;
        $database->sql_query("DELETE FROM chat_searching WHERE id = ? AND user_id = ?", array($search_id, $id));
        return true;
    }
    function setTyping($chat_token)
    {
        $id = $this->user_id;
        global $database;
        $check_chat = $database->sql_query("SELECT * FROM chat WHERE chat_token = ?", array($chat_token))['result'][0];
        if ($check_chat['chat_token'] == $chat_token) {
            $send_to = $check_chat['first_user'] == $id ? $check_chat['second_user'] : $check_chat['first_user'];
            $typing_data = ['type' => "typing"];
            pingUser($send_to, $typing_data);
        }
        return true;
    }
    function closeChat($chat_token)
    {
        $id = $this->user_id;
        global $database;
        $check_chat = $database->sql_query("SELECT * FROM chat WHERE chat_token = ?", array($chat_token))['result'][0];
        if ($check_chat['chat_token'] == $chat_token) {
            $send_to = $check_chat['first_user'] == $id ? $check_chat['second_user'] : $check_chat['first_user'];
            $close_data = ['type' => "chat_closed"];
            pingUser($send_to, $close_data);
            $database->sql_query("DELETE FROM chat WHERE chat_token = ?", array($chat_token));
        }
        return true;
    }
    function sendMessage($chat_token, $text)
    {
        $id = $this->user_id;
        global $database;
        $check_chat = $database->sql_query("SELECT * FROM chat WHERE chat_token = ?", array($chat_token))['result'][0];
        if ($check_chat['chat_token'] == $chat_token) {
            $send_to = $check_chat['first_user'] == $id ? $check_chat['second_user'] : $check_chat['first_user'];
            $new_msg_data = ['type' => "new_message", 'msg_id' => "{$check_chat['next_msg']}", 'text' => $text];
            $database->sql_query("UPDATE chat SET next_msg = next_msg+1 WHERE chat_token = ?", array($chat_token));
            pingUser($send_to, $new_msg_data);
            return $check_chat['next_msg'];
        }else{
            return false;
        }
    }
    function deleteMessage($chat_token, $msg_id)
    {
        $id = $this->user_id;
        global $database;
        $check_chat = $database->sql_query("SELECT * FROM chat WHERE chat_token = ?", array($chat_token))['result'][0];
        if ($check_chat['chat_token'] == $chat_token) {
            $send_to = $check_chat['first_user'] == $id ? $check_chat['second_user'] : $check_chat['first_user'];
            $del_msg_data = ['type' => "del_message", 'msg_id' => $msg_id];
            pingUser($send_to, $del_msg_data);
        }
        return true;
    }
}
