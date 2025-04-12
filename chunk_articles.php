<?php
/**
 * Chunking script for articles with OCR and file extraction data.
 * 
 * Reads input JSON, splits content into semantically coherent chunks,
 * and outputs a new JSON file suitable for RAG.
 */

$inputFile = 'tatce_articles_with_extracted_text_retry_8.json';  // Adjust path if needed
$outputFile = 'chunks1.json';  // Output file

// Read the input JSON file
$jsonData = file_get_contents($inputFile);
if ($jsonData === false) {
    // Stop execution if file cannot be read
    die("Error reading input JSON file: $inputFile");
}

// Decode JSON into associative array
$articles = json_decode($jsonData, true);
if ($articles === null) {
    // Stop execution if JSON is invalid
    die("Error decoding input JSON: " . json_last_error_msg());
}

$allChunks = [];      // Array to hold all generated chunks
$chunkCounter = 0;    // Counter for unique chunk IDs

// Iterate over each article in the input data
foreach ($articles as $article) {
    $originalUrl = $article['url'] ?? 'unknown_url';
    $originalTitle = $article['title'] ?? 'unknown_title';
    $originalDate = $article['date'] ?? null;

    // Process 'content' field: split main article content into chunks
    if (!empty($article['content'])) {
        $contentChunks = splitTextIntoChunks($article['content'], $originalUrl, $originalTitle, $originalDate, 'content', $chunkCounter);
        $allChunks = array_merge($allChunks, $contentChunks);
    }

    // Process 'images_ocr_text' field: split OCR text from images into chunks
    if (!empty($article['images_ocr_text']) && is_array($article['images_ocr_text'])) {
        foreach ($article['images_ocr_text'] as $imageOcr) {
            if (!empty($imageOcr['ocr_text'])) {
                $imageUrl = $imageOcr['image_url'] ?? null;
                $ocrChunks = splitTextIntoChunks(
                    $imageOcr['ocr_text'],
                    $originalUrl,
                    $originalTitle,
                    $originalDate,
                    'image_ocr',
                    $chunkCounter,
                    ['image_url' => $imageUrl]
                );
                $allChunks = array_merge($allChunks, $ocrChunks);
            }
        }
    }

    // Process 'files_extracted_text' field: split extracted text from files into chunks
    if (!empty($article['files_extracted_text']) && is_array($article['files_extracted_text'])) {
        foreach ($article['files_extracted_text'] as $fileText) {
            if (!empty($fileText['extracted_text'])) {
                // Skip chunks that are just OCR errors
                if (strpos($fileText['extracted_text'], 'OCR Error:') === 0) {
                    error_log("Skipping OCR Error chunk from: " . $originalUrl);
                    continue;
                }
                $fileUrl = $fileText['file_url'] ?? null;
                $fileLinkText = $fileText['link_text'] ?? null;
                $fileChunks = splitTextIntoChunks(
                    $fileText['extracted_text'],
                    $originalUrl,
                    $originalTitle,
                    $originalDate,
                    'file_extraction',
                    $chunkCounter,
                    ['file_url' => $fileUrl, 'link_text' => $fileLinkText]
                );
                $allChunks = array_merge($allChunks, $fileChunks);
            }
        }
    }
}

// Save output: encode all chunks to JSON
$outputJson = json_encode($allChunks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($outputJson === false) {
    // Stop execution if encoding fails
    die("Error encoding chunked data to JSON: " . json_last_error_msg());
}

// Write the chunked data to the output file
if (file_put_contents($outputFile, $outputJson) === false) {
    die("Error writing chunked data to output file: $outputFile");
}

echo "Successfully created chunked data in: $outputFile\n";
echo "Total chunks created: $chunkCounter\n";

/**
 * Splits text into manageable chunks (paragraphs).
 *
 * @param string $text The text to split.
 * @param string $url Original article URL.
 * @param string $title Original article title.
 * @param string|null $date Original article date.
 * @param string $sourceType 'content', 'image_ocr', or 'file_extraction'.
 * @param int &$chunkCounter Reference to a global counter for unique IDs.
 * @param array $additionalMeta Optional additional metadata for the chunk.
 * @return array An array of chunk objects.
 */
function splitTextIntoChunks(string $text, string $url, string $title, ?string $date, string $sourceType, int &$chunkCounter, array $additionalMeta = []): array {
    $chunks = [];
    // Normalize line endings and trim whitespace
    $cleanedText = trim(str_replace("\r\n", "\n", $text));
    if (empty($cleanedText)) {
        // Return empty array if text is empty
        return [];
    }

    // Split text into paragraphs (chunks) by two or more newlines
    $paragraphs = preg_split('/\n{2,}/', $cleanedText);
    $minChunkLength = 20; // Minimum length for a chunk to be included

    foreach ($paragraphs as $paragraph) {
        $paragraph = trim($paragraph);
        // Only include sufficiently long paragraphs, or always include if only one paragraph
        $chunkId = "chunk_" . str_pad(++$chunkCounter, 6, '0', STR_PAD_LEFT);

        // Build chunk data array
        $chunkData = [
            'chunk_id' => $chunkId,
            'original_article_url' => $url,
            'original_article_title' => $title,
            'original_article_date' => $date,
            'source_type' => $sourceType,
            'text' => $paragraph
        ];

        // Merge in any additional metadata (e.g., image/file URLs)
        $chunkData = array_merge($chunkData, $additionalMeta);
        $chunks[] = $chunkData;
    }
    return $chunks;
}
?>