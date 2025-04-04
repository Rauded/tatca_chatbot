<?php
require_once __DIR__ . '/../src/bootstrap.php';

use src\Service\OpenAIService;
use src\Service\RAGService;

$processedDir = __DIR__ . '/../data/processed/';
if (!is_dir($processedDir)) {
    echo "Processed data directory not found: $processedDir\n";
    exit(1);
}

$openAI = new OpenAIService();
$rag = new RAGService($openAI);

$files = glob($processedDir . '*.txt');
foreach ($files as $file) {
    echo "Processing file: $file\n";
    $text = file_get_contents($file);
    $chunks = chunkText($text, 500, 50); // chunk size 500 chars, overlap 50 chars

    foreach ($chunks as $chunk) {
        $embedding = $openAI->getEmbedding($chunk);
        $rag->addToIndex($chunk, $embedding, ['source' => basename($file)]);
        echo ".";
    }
    echo "\n";
}

function chunkText(string $text, int $chunkSize, int $overlap): array
{
    $chunks = [];
    $start = 0;
    $len = strlen($text);
    while ($start < $len) {
        $end = min($start + $chunkSize, $len);
        $chunk = substr($text, $start, $end - $start);
        $chunks[] = $chunk;
        $start += ($chunkSize - $overlap);
    }
    return $chunks;
}
?>