<?php

// -----------------------------------------------------------------------------
// Script: generate_embeddings.php
// Purpose: Loads text chunks, sends them to OpenAI API for embedding generation,
//          and saves the enriched data to a new JSON file.
// -----------------------------------------------------------------------------

require 'vendor/autoload.php'; // If using Composer for libraries like GuzzleHttp

// Load environment variables from .env file (for API keys, etc.)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

// --- Configuration ---
$inputFile = 'chunks_12.4_converted_with_date_in_text.json'; // Input file from chunking step
$outputFile = 'chunks_with_embeddings_12.4_added_text_and_image.json'; // Output file
$openAiApiKey = $_ENV['OPENAI_API_KEY'] ?? 'YOUR_OPENAI_API_KEY'; // IMPORTANT: Load from environment variable or secure config
$embeddingModel = 'text-embedding-ada-002'; // OpenAI embedding model
$openaiApiUrl = 'https://api.openai.com/v1/embeddings'; // OpenAI API endpoint
$rateLimitDelayMicroseconds = 600000; // 0.6 seconds delay between requests (adjust based on your OpenAI rate limits)

if (!$openAiApiKey) {
    // Stop execution if API key is missing
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
    'timeout' => 30.0, // Request timeout in seconds
]);

// --- Process Chunks and Get Embeddings ---
$chunksWithEmbeddings = []; // Array to hold enriched chunks
$processedCount = 0;
$totalChunks = count($chunks);

foreach ($chunks as $chunk) {
    // Skip chunks with empty text
    if (empty($chunk['text']) || trim($chunk['text']) === '') {
        echo "Skipping chunk ID {$chunk['chunk_id']} due to empty text.\n";
        // Optionally, add chunk without embedding or skip entirely
        // $chunksWithEmbeddings[] = $chunk;
        continue;
    }

    // Concatenate fields for embedding: original_article_url, original_article_title, original_article_date, source_type, text
    $fields = [
        $chunk['original_article_url'] ?? '',
        $chunk['original_article_title'] ?? '',
        $chunk['original_article_date'] ?? '',
        $chunk['source_type'] ?? '',
        $chunk['text'] ?? ''
    ];
    // Remove empty fields and join with ' | '
    $textToEmbed = implode(' | ', array_filter($fields, function($v) { return trim($v) !== ''; }));
    echo "Prompt being sent to OpenAI API:\n$textToEmbed\n";

    echo "Processing chunk " . ($processedCount + 1) . "/$totalChunks (ID: {$chunk['chunk_id']})... ";

    try {
        // Send POST request to OpenAI API to get embedding
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

        // Parse API response
        $responseBody = json_decode($response->getBody()->getContents(), true);

        if (isset($responseBody['data'][0]['embedding'])) {
            // Add embedding vector to the chunk
            $chunk['embedding'] = $responseBody['data'][0]['embedding'];
            $chunksWithEmbeddings[] = $chunk;
            echo "Success.\n";
        } else {
            echo "Failed. Embedding not found in response.\n";
            // Decide how to handle: skip chunk, retry, add placeholder, etc.
            // For simplicity, we'll skip adding the embedding here
            // $chunksWithEmbeddings[] = $chunk;
            error_log("Failed to get embedding for chunk ID {$chunk['chunk_id']}: Embedding data missing in API response.");
        }

    } catch (RequestException $e) {
        // Handle HTTP request errors (network, API, etc.)
        echo "Failed. API Error: " . $e->getMessage() . "\n";
        if ($e->hasResponse()) {
            error_log("API Error Response Body: " . $e->getResponse()->getBody());
        }
        // Decide how to handle: skip chunk, retry later, stop script, etc.
        // For simplicity, we'll skip adding the embedding here
        // $chunksWithEmbeddings[] = $chunk;
        error_log("API Request Exception for chunk ID {$chunk['chunk_id']}: " . $e->getMessage());
    } catch (Exception $e) {
        // Handle any other general errors
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