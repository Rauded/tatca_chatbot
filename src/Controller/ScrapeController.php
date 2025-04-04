<?php
namespace src\Controller;

class ScrapeController
{
    public function handleScrape(array $vars = []): void
    {
        header('Content-Type: application/json');

        try {
            ob_start();
            include __DIR__ . '/../../scripts/scrape_data.php';
            ob_end_clean();

            echo json_encode(['status' => 'success', 'message' => 'Scraping completed']);
        } catch (\Throwable $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
?>