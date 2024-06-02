<?php
class base
{
    function sql_query($query, $params = null, $return_id = false)
    {
        //  $query = str_replace("`", "\"", $query);;
        $host = 'localhost';
        $user = 'chat_data_usr';
        $pass = 'CeoeTjHMEorQBp7B';
        //      $pass = 'R4UE0CSwBvscO8lW';
        $db_name = 'chat_data';
        $link = new PDO('mysql:dbname=' . $db_name . ';host=' . $host, $user, $pass);
        $link->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
        $link->exec("set names utf8mb4");
        $sth = $link->prepare($query);
        $result_succ = $sth->execute($params);


        // $port = 5432; // Порт PostgreSQL
        // $dsn = "pgsql:host=$host;port=$port;dbname=$db_name;user=$user;password=$pass";
        // $link = new PDO($dsn);
        // $link->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
        // $link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Добавляем для лучшей обработки ошибок
        // $sth = $link->prepare($query);
        // $result_succ = $sth->execute($params);

        if ($return_id)
            $result['insert_id'] = $link->lastInsertId();
        $result['result'] = $sth->fetchAll(PDO::FETCH_ASSOC);
        $result['succ'] = $result_succ;
        $link = null;
        return $result;
    }
}
