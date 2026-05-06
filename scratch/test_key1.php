<?php
require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/../.env');

// Manual check of the first key
$key1 = "AIzaSyAdNwqtP2TqovwmmYGP76X7I2LoQ8YLlWU";

echo "Testing Key 1 ($key1)... ";
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=" . $key1;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'contents' => [['parts' => [['text' => 'Hi']]]]
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$output = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "SUCCESS!\n";
} else {
    $data = json_decode($output, true);
    $msg = $data['error']['message'] ?? 'Unknown error';
    echo "FAILED ($httpCode): $msg\n";
}
