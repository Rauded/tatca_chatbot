<?php

require 'vendor/autoload.php'; // If using Composer for libraries like GuzzleHttp

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

// --- Configuration ---
$inputFile = 'chunks.json'; // Input file from chunking step
$outputFile = 'chunks_with_embeddings1.json'; // Output file
$openAiApiKey = $_ENV['OPENAI_API_KEY'] ?? 'YOUR_OPENAI_API_KEY'; // IMPORTANT: Load from environment variable or secure config
$embeddingModel = 'text-embedding-ada-002';
$openaiApiUrl = 'https://api.openai.com/v1/embeddings';
$rateLimitDelayMicroseconds = 600000; // 0.6 seconds delay between requests (adjust based on your OpenAI rate limits)

if (!$openAiApiKey) {
    die("Error: OPENAI_API_KEY environment variable not set.\n");
}

// --- Load Chunk Data ---
echo "Loading chunks from: $inputFile\n";
$jsonData = file_get_contents($inputFile);
if ($jsonData === false) {
    die("Error reading input JSON file: $inputFile\n");
}
$chunks = json_decode($jsonData, true);
if ($chunks === null) {
    die("Error decoding input JSON: " . json_last_error_msg() . "\n");
}
echo "Loaded " . count($chunks) . " chunks.\n";

// --- Prepare HTTP Client (using GuzzleHttp as an example) ---
$client = new Client([
    'timeout' => 30.0, // Request timeout
]);

// --- Process Chunks and Get Embeddings ---
$chunksWithEmbeddings = [];
$processedCount = 0;
$totalChunks = count($chunks);

foreach ($chunks as $chunk) {
    if (empty($chunk['text']) || trim($chunk['text']) === '') {
        echo "Skipping chunk ID {$chunk['chunk_id']} due to empty text.\n";
        // Add chunk without embedding if needed, or skip entirely
        // $chunksWithEmbeddings[] = $chunk; // If you want to keep it
        continue;
    }

    $textToEmbed = $chunk['text'];

    echo "Processing chunk " . ($processedCount + 1) . "/$totalChunks (ID: {$chunk['chunk_id']})... ";

    try {
        $response = $client->post($openaiApiUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $openAiApiKey,
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'input' => $textToEmbed,
                'model' => $embeddingModel,
            ],
        ]);

        $responseBody = json_decode($response->getBody()->getContents(), true);

        if (isset($responseBody['data'][0]['embedding'])) {
            $chunk['embedding'] = $responseBody['data'][0]['embedding']; // Add embedding vector
            $chunksWithEmbeddings[] = $chunk;
            echo "Success.\n";
        } else {
            echo "Failed. Embedding not found in response.\n";
            // Decide how to handle: skip chunk, retry, add placeholder?
            // For simplicity, we'll skip adding the embedding here
            // $chunksWithEmbeddings[] = $chunk; // Add without embedding
            error_log("Failed to get embedding for chunk ID {$chunk['chunk_id']}: Embedding data missing in API response.");
        }

    } catch (RequestException $e) {
        echo "Failed. API Error: " . $e->getMessage() . "\n";
        if ($e->hasResponse()) {
            error_log("API Error Response Body: " . $e->getResponse()->getBody());
        }
        // Decide how to handle: skip chunk, retry later, stop script?
        // For simplicity, we'll skip adding the embedding here
        // $chunksWithEmbeddings[] = $chunk; // Add without embedding
        error_log("API Request Exception for chunk ID {$chunk['chunk_id']}: " . $e->getMessage());
    } catch (Exception $e) {
        echo "Failed. General Error: " . $e->getMessage() . "\n";
        error_log("General Exception for chunk ID {$chunk['chunk_id']}: " . $e->getMessage());
    }

    $processedCount++;
    usleep($rateLimitDelayMicroseconds); // IMPORTANT: Respect rate limits!
}

// --- Save Enriched Data ---
echo "Saving chunks with embeddings to: $outputFile\n";
$outputJson = json_encode($chunksWithEmbeddings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if ($outputJson === false) {
    die("Error encoding chunked data with embeddings to JSON: " . json_last_error_msg() . "\n");
}

if (file_put_contents($outputFile, $outputJson) === false) {
    die("Error writing chunked data with embeddings to output file: $outputFile\n");
}

echo "Successfully generated embeddings and saved to: $outputFile\n";
echo "Total chunks processed: $processedCount\n";
echo "Total chunks saved with embeddings: " . count($chunksWithEmbeddings) . "\n";

?>