<?php
require_once("/var/www/fastuser/data/www/dev.cr-house.ru/vendor/autoload.php");

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\Middleware\AuthTokenMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

class FCMNotifier
{

    function __construct()
    {
    }
    public static function sendNotification($deviceToken, $add_data)
    {

        $serviceAccountFilePath = 'anonchat-fc007-fceb476d27ca.json';
        $serviceAccountJson = file_get_contents($serviceAccountFilePath);
        $serviceAccount = json_decode($serviceAccountJson, true);
        $credentials = new ServiceAccountCredentials([], $serviceAccount);
        $middleware = new AuthTokenMiddleware($credentials);

        // Создаем стек обработчиков Guzzle
        $stack = HandlerStack::create();
        $stack->push($middleware);

        // Создаем клиент Guzzle с использованием стека обработчиков
        $client = new Client(['handler' => $stack]);
        $data = [
            'message' => [
                'name' => 'test',
                'token' => $deviceToken, // Токен устройства, куда отправляется уведомление
                'data' => $add_data
            ]
        ];
        $jwtToken = Firebase\JWT\JWT::encode([
            'iss' => $serviceAccount['client_email'],
            'sub' => $serviceAccount['client_email'],
            'aud' => 'https://fcm.googleapis.com/',
            'iat' => time(),
            'exp' => time() + 3600,
        ], $serviceAccount['private_key'], 'RS256');
        
        // Формируем параметры запроса, включая учетные данные
        $options = [
            'json' => $data,
            'headers' => [
                'Authorization' => 'Bearer ' . $jwtToken
            ]
        ];
        
        // Отправляем запрос к FCM API
        $response = $client->post('https://fcm.googleapis.com/v1/projects/anonchat-fc007/messages:send', $options);
        // Выводим результат запроса
        return $response->getBody();
   
    }
}
