<?php
require_once __DIR__ . '/../src/bootstrap.php';

use src\Service\OCRService;

$baseUrl = 'https://www.tatce.cz/prakticke-info/aktuality/';
$page = 1;
$processedDir = __DIR__ . '/../data/processed/';

if (!is_dir($processedDir)) {
    mkdir($processedDir, 0777, true);
}

$ocr = new OCRService();

while (true) {
    $url = $baseUrl . ($page > 1 ? '?page=' . $page : '');
    echo "Fetching page: $url\n";
    $html = @file_get_contents($url);
    if (!$html) {
        echo "Failed to fetch page or no more pages.\n";
        break;
    }

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($doc);
    $links = $xpath->query("//div[contains(@class,'article-list')]//a[contains(@href, '/prakticke-info/aktuality/')]");
    if ($links->length === 0) {
        echo "No articles found on page $page. Stopping.\n";
        break;
    }

    foreach ($links as $link) {
        $href = $link->getAttribute('href');
        $title = trim($link->nodeValue);
        $articleUrl = 'https://www.tatce.cz' . $href;

        echo "Fetching article: $articleUrl\n";
        $articleHtml = @file_get_contents($articleUrl);
        if (!$articleHtml) {
            echo "Failed to fetch article.\n";
            continue;
        }

        $articleDoc = new DOMDocument();
        libxml_use_internal_errors(true);
        $articleDoc->loadHTML($articleHtml);
        libxml_clear_errors();

        $articleXpath = new DOMXPath($articleDoc);
        $contentNodes = $articleXpath->query("//div[contains(@class,'article-detail')]");
        $content = '';
        foreach ($contentNodes as $node) {
            $content .= $articleDoc->saveHTML($node);
        }

        $textContent = strip_tags($content);

        // OCR on images inside article content
        $imgNodes = [];
        foreach ($contentNodes as $node) {
            $imgs = $node->getElementsByTagName('img');
            foreach ($imgs as $img) {
                $imgUrl = $img->getAttribute('src');
                if (strpos($imgUrl, 'http') !== 0) {
                    $imgUrl = 'https://www.tatce.cz' . $imgUrl;
                }
                echo "Fetching image for OCR: $imgUrl\n";
                $imgData = @file_get_contents($imgUrl);
                if ($imgData) {
                    $tmpFile = tempnam(sys_get_temp_dir(), 'ocr_img_');
                    file_put_contents($tmpFile, $imgData);
                    $ocrText = $ocr->extractText($tmpFile);
                    unlink($tmpFile);
                    if ($ocrText) {
                        $textContent .= "\n[Image OCR Text]:\n" . $ocrText . "\n";
                    }
                }
            }
        }

        $safeTitle = preg_replace('/[^a-z0-9]+/i', '_', $title);
        $fileName = $processedDir . $safeTitle . '.txt';
        file_put_contents($fileName, $textContent);
        echo "Saved article with OCR to: $fileName\n";
    }

    $page++;
}
?>