<?php
require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/../.env');

$openAIKey = $_ENV['OPENAI_API_KEY'] ?? null;

if (!$openAIKey) {
    die("No OPENAI_API_KEY found in .env\n");
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/chat/completions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'model' => 'gpt-4o-mini',
    'messages' => [
        ['role' => 'user', 'content' => 'Hello, are you working?']
    ],
    'max_tokens' => 10
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $openAIKey
]);

$output = curl_exec($ch);
if (curl_errno($ch)) {
    die(curl_error($ch));
}
curl_close($ch);

$data = json_decode($output, true);
if (isset($data['choices'][0]['message']['content'])) {
    echo "OpenAI Success: " . $data['choices'][0]['message']['content'] . "\n";
} else {
    echo "OpenAI Error:\n" . $output . "\n";
}
