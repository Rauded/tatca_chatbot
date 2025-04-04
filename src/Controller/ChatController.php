<?php
namespace src\Controller;

use src\Service\OpenAIService;
use src\Service\RAGService;

class ChatController
{
    public function handleRequest(array $vars = []): void
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        $userMessage = $input['message'] ?? '';

        $openAI = new OpenAIService();
        $rag = new RAGService($openAI);

        $context = $rag->retrieveContext($userMessage);

        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful AI assistant for city information. Use the provided context to answer.'],
            ['role' => 'system', 'content' => 'Context: ' . $context],
            ['role' => 'user', 'content' => $userMessage],
        ];

        $reply = $openAI->getChatCompletion($messages);

        $response = [
            'reply' => $reply
        ];

        echo json_encode($response);
    }
}
?>