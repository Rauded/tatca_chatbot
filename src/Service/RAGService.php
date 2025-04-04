<?php
namespace src\Service;

class RAGService
{
    private OpenAIService $openAIService;
    private string $indexFile;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
        $this->indexFile = __DIR__ . '/../../data/vector_index/index.json';
        if (!file_exists(dirname($this->indexFile))) {
            mkdir(dirname($this->indexFile), 0777, true);
        }
        if (!file_exists($this->indexFile)) {
            file_put_contents($this->indexFile, json_encode([]));
        }
    }

    public function addToIndex(string $text, array $embedding, array $metadata = []): void
    {
        $entries = json_decode(file_get_contents($this->indexFile), true) ?? [];
        $entries[] = [
            'embedding' => $embedding,
            'text' => $text,
            'metadata' => $metadata,
        ];
        file_put_contents($this->indexFile, json_encode($entries));
    }

    public function retrieveContext(string $query, int $topK = 3): string
    {
        $queryEmbedding = $this->openAIService->getEmbedding($query);
        $entries = json_decode(file_get_contents($this->indexFile), true) ?? [];

        $scored = [];
        foreach ($entries as $entry) {
            $score = $this->cosineSimilarity($queryEmbedding, $entry['embedding']);
            $scored[] = ['score' => $score, 'text' => $entry['text']];
        }

        usort($scored, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        $topChunks = array_slice($scored, 0, $topK);
        $context = implode("\n", array_column($topChunks, 'text'));

        return $context ?: "No relevant information found.";
    }

    private function cosineSimilarity(array $vecA, array $vecB): float
    {
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        $len = min(count($vecA), count($vecB));
        for ($i = 0; $i < $len; $i++) {
            $dot += $vecA[$i] * $vecB[$i];
            $normA += $vecA[$i] ** 2;
            $normB += $vecB[$i] ** 2;
        }
        if ($normA == 0 || $normB == 0) {
            return 0.0;
        }
        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
?>