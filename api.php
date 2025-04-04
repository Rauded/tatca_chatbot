<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
header('Content-Type: application/json');

if (!isset($_SESSION['message_history'])) {
    $_SESSION['message_history'] = "message history...\n";
}

$apiKey = $_ENV['OPENAI_API_KEY'] ?? 'YOUR_OPENAI_API_KEY';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$prompt = trim($input['prompt'] ?? '');
$_SESSION['message_history'] .= "User: $prompt\n";

if (!$prompt) {
    http_response_code(400);
    echo json_encode(['error' => 'Prompt is required']);
    exit;
}

$url = 'https://api.openai.com/v1/chat/completions';
$data = [
    'model' => 'gpt-4o-mini-2024-07-18',
    'messages' => [
        ['role' => 'user', 'content' => $_SESSION['message_history']]
    ],
    'temperature' => 0.7,
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey,
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$result = curl_exec($ch);
if ($result === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Request failed']);
    curl_close($ch);
    exit;
}

$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($status !== 200) {
    http_response_code($status);
    echo json_encode(['error' => 'API error', 'status' => $status]);
    curl_close($ch);
    exit;
}

$response = json_decode($result, true);
$botMessage = $response['choices'][0]['message']['content'] ?? '';
$_SESSION['message_history'] .= "Bot: $botMessage\n";

curl_close($ch);

echo json_encode([
    'response' => $botMessage,
    'message_history' => $_SESSION['message_history']
]);