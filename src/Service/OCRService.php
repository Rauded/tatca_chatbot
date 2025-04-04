<?php
namespace src\Service;

class OCRService
{
    private string $apiKey;
    private string $apiUrl = 'https://api.ocr.space/parse/image';

    public function __construct()
    {
        $this->apiKey = $_ENV['OCR_API_KEY'] ?? '';
    }

    public function extractText(string $filePath): string
    {
        if (!file_exists($filePath)) {
            return '';
        }

        $postData = [
            'isOverlayRequired' => 'false',
            'language' => 'eng',
        ];

        $fileData = curl_file_create($filePath);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . $this->apiKey,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array_merge($postData, ['file' => $fileData]));

        $result = curl_exec($ch);
        if ($result === false) {
            curl_close($ch);
            return '';
        }
        curl_close($ch);

        $response = json_decode($result, true);
        if (isset($response['IsErroredOnProcessing']) && !$response['IsErroredOnProcessing']) {
            $parsedResults = $response['ParsedResults'][0]['ParsedText'] ?? '';
            return $parsedResults;
        } else {
            return '';
        }
    }
}
?>