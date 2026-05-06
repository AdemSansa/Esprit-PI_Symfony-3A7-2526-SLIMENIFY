<?php
require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/../.env');

$hfToken = $_ENV['HF_TOKEN'] ?? null;

if (!$hfToken) {
    die("No HF_TOKEN found in .env\n");
}

$model = "Qwen/Qwen2.5-72B-Instruct";
$url = "https://api-inference.huggingface.co/models/" . $model;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'inputs' => 'Hello, how are you?',
    'parameters' => ['max_new_tokens' => 10]
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $hfToken
]);

$output = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "Hugging Face Success: " . $output . "\n";
} else {
    echo "Hugging Face Error ($httpCode):\n" . $output . "\n";
}
