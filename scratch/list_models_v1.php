<?php
require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/../.env');

$key = $_ENV['GEMINI_API_KEY'] ?? null;

$url = "https://generativelanguage.googleapis.com/v1/models?key=" . $key;
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$output = curl_exec($ch);
curl_close($ch);

$data = json_decode($output, true);
if (isset($data['models'])) {
    foreach ($data['models'] as $model) {
        echo $model['name'] . "\n";
    }
} else {
    echo "Error:\n" . $output . "\n";
}
