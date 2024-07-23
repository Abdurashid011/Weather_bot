<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

$botToken = '7359283783:AAEiCABH6nsea9ukVqf7Md8xnibL7FEOdF8';
$apiKey = '3e9ffa11fbf6ddf965a955282dd45513';
$apiUrl = "https://api.telegram.org/bot$botToken/";

date_default_timezone_set("Asia/Tashkent");

function sendMessage($chatId, $message, $buttons = null)
{
    global $apiUrl;

    $data = [
        'chat_id' => $chatId,
        'text' => $message
    ];

    if ($buttons) {
        $data['reply_markup'] = json_encode($buttons);
    }

    $client = new Client();
    try {
        $response = $client->post($apiUrl . "sendMessage", [
            'json' => $data
        ]);
    } catch (RequestException $e) {
        error_log('Error sending message: ' . $e->getMessage());
    }
}

function getWeatherIcon($weatherCondition, $isDaytime)
{
    switch ($weatherCondition) {
        case 'Clear':
            return $isDaytime ? '☀️' : '🌙';
        case 'Clouds':
            return $isDaytime ? '⛅' : '☁️';
        case 'Drizzle':
        case 'Rain':
            return '🌧️';
        case 'Thunderstorm':
            return '⛈️';
        case 'Snow':
            return '❄️';
        case 'Mist':
            return '🌁';
        case 'Tornado':
            return '🌫️';
        default:
            return '❓';
    }
}

function formatTime($timestamp, $timezoneOffset)
{
    $timezone = timezone_name_from_abbr('', $timezoneOffset, 0);
    if (!$timezone) {
        $timezone = timezone_name_from_abbr('', $timezoneOffset * 3600, 0);
    }
    if (!$timezone) {
        $timezone = 'UTC';
    }
    $dateTime = new DateTime("@$timestamp");
    $dateTime->setTimezone(new DateTimeZone($timezone));
    return $dateTime->format('H:i');
}

function getWeather($city, $apiKey)
{
    $client = new Client();
    try {
        $response = $client->get("https://api.openweathermap.org/data/2.5/weather", [
            'query' => [
                'q' => $city,
                'appid' => $apiKey,
                'units' => 'metric'
            ]
        ]);

        return json_decode($response->getBody(), true);
    } catch (RequestException $e) {
        error_log('Error fetching weather data: ' . $e->getMessage());
        return null;
    }
}

$update = json_decode(file_get_contents("php://input"), true);

if (isset($update['message'])) {
    $message = $update['message'];
    $chatId = $message['chat']['id'];
    $text = $message['text'];

    // Check if the message is a command
    if (strpos($text, '/start') === 0) {
        $buttons = [
            'keyboard' => [
                ['Toshkent', 'Sirdaryo', 'Jizzax'],
                ['Samarqand', 'Buxoro', 'Navoiy'],
                ['Namangan', 'Fargʻona', 'Andijon'],
                ['Qashqadaryo', 'Surxondaryo', 'Xorazm']
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ];

        sendMessage($chatId, "Quyidagi shaharlarning ob-havo ma'lumotlarini ko'ring:", $buttons);
    } else if (in_array($text, ['Toshkent', 'Sirdaryo', 'Jizzax', 'Samarqand', 'Buxoro', 'Navoiy', 'Namangan', 'Fargʻona', 'Andijon', 'Qashqadaryo', 'Surxondaryo', 'Xorazm'])) {
        $city = $text;
        $weatherData = getWeather($city, $apiKey);

        if ($weatherData && isset($weatherData['main']['temp'])) {
            $temp = $weatherData['main']['temp'];
            $feels_like = $weatherData['main']['feels_like'];
            $weatherMain = $weatherData['weather'][0]['main'];
            $description = $weatherData['weather'][0]['description'];
            $clouds = $weatherData['clouds']['all'];
            $humidity = $weatherData['main']['humidity'];
            $wind = $weatherData['wind']['speed'];
            $pressure = $weatherData['main']['pressure'];
            $icon = $weatherData['weather'][0]['icon'];
            $sunrise = $weatherData['sys']['sunrise'];
            $sunset = $weatherData['sys']['sunset'];
            $timezone = $weatherData['timezone'];
            $sunrise = isset($sunrise) ? formatTime($sunrise, $timezone) : 'N/A';
            $sunset = isset($sunset) ? formatTime($sunset, $timezone) : 'N/A';

            $isDay = date('H') > 6 && date('H') < 18;
            $icon = getWeatherIcon($weatherMain, $isDay);

            $currentDate = date('j-F');

            sendMessage($chatId, "
🗓 $currentDate 
🔹 $city shahridagi ob-havo ma'lumoti
$icon $weatherMain
Temperatura: $temp °C
Kabi seziladi: $feels_like °C

———

Bulut: $clouds %
Namlik: $humidity %
Shamol tezligi: $wind m/s
Bosim: $pressure mm sim. ust.
🌅 Quyosh chiqishi: $sunrise
🌆 Quyosh botishi: $sunset
            ");
        } else {
            sendMessage($chatId, "Kechirasiz, $city uchun ob-havo ma'lumotlarini topa olmadim.");
        }
    } else {
        sendMessage($chatId, "Notanish buyruq. Ob-havo ma'lumotlarini olish uchun /start buyrug'ini ishlating.");
    }
}
