<?php
namespace src\Service;

class OpenAIService
{
    private string $apiKey;
    private string $apiBase = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->apiKey = $_ENV['OPENAI_API_KEY'] ?? '';
    }

    public function getChatCompletion(array $messages): string
    {
        $url = $this->apiBase . '/chat/completions';
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => $messages,
            'temperature' => 0.7,
        ];
        $response = $this->makeRequest($url, $data);
        return $response['choices'][0]['message']['content'] ?? '';
    }

    public function getEmbedding(string $text): array
    {
        $url = $this->apiBase . '/embeddings';
        $data = [
            'model' => 'text-embedding-ada-002',
            'input' => $text,
        ];
        $response = $this->makeRequest($url, $data);
        return $response['data'][0]['embedding'] ?? [];
    }

    private function makeRequest(string $url, array $data): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $result = curl_exec($ch);
        if ($result === false) {
            curl_close($ch);
            return [];
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status !== 200) {
            return [];
        }

        return json_decode($result, true) ?? [];
    }
}
?>