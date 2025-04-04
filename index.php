<?php
$apiKey = $_ENV['OPENAI_API_KEY'] ?? 'YOUR_OPENAI_API_KEY';

function callOpenAI($prompt, $apiKey) {
    $url = 'https://api.openai.com/v1/chat/completions';
    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
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
        curl_close($ch);
        return 'Request failed';
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status !== 200) {
        return 'API error: HTTP ' . $status;
    }

    $response = json_decode($result, true);
    return $response['choices'][0]['message']['content'] ?? 'No response';
}

$responseText = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['prompt'])) {
    $prompt = trim($_POST['prompt']);
    $responseText = callOpenAI($prompt, $apiKey);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Chatbot</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        textarea { width: 100%; height: 100px; }
        .response { margin-top: 20px; padding: 10px; background: #f0f0f0; }
    </style>
</head>
<body>
    <h1>Simple Chatbot</h1>
    <form method="post">
        <textarea name="prompt" placeholder="Enter your message..."><?php echo isset($_POST['prompt']) ? htmlspecialchars($_POST['prompt']) : ''; ?></textarea><br>
        <button type="submit">Send</button>
    </form>

    <?php if ($responseText): ?>
        <div class="response">
            <strong>Response:</strong><br>
            <?php echo nl2br(htmlspecialchars($responseText)); ?>
        </div>
    <?php endif; ?>
</body>
</html>