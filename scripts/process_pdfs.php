<?php
require_once __DIR__ . '/../src/bootstrap.php';

use src\Service\OCRService;

$rawDir = __DIR__ . '/../data/raw/';
$processedDir = __DIR__ . '/../data/processed/';

if (!is_dir($rawDir)) {
    echo "Raw data directory not found: $rawDir\n";
    exit(1);
}
if (!is_dir($processedDir)) {
    mkdir($processedDir, 0777, true);
}

$ocr = new OCRService();

$files = glob($rawDir . '*.{pdf,jpg,jpeg,png}', GLOB_BRACE);
foreach ($files as $file) {
    echo "Processing file: $file\n";
    $text = $ocr->extractText($file);
    if ($text) {
        $baseName = pathinfo($file, PATHINFO_FILENAME);
        $outFile = $processedDir . $baseName . '.txt';
        file_put_contents($outFile, $text);
        echo "Saved extracted text to: $outFile\n";
    } else {
        echo "Failed to extract text from: $file\n";
    }
}
?>