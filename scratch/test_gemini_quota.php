<?php
require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/../.env');

$key = $_ENV['GEMINI_API_KEY'] ?? null;

if (!$key) {
    die("No GEMINI_API_KEY found in .env\n");
}

$models = [
    'gemini-2.5-flash',
];

foreach ($models as $modelName) {
    echo "Testing $modelName... ";
    $url = "https://generativelanguage.googleapis.com/v1beta/models/$modelName:generateContent?key=" . $key;
    
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
        break;
    } else {
        $data = json_decode($output, true);
        $msg = $data['error']['message'] ?? 'Unknown error';
        echo "FAILED ($httpCode): $msg\n";
    }
}
