<?php
/**
 * Calculates the cosine similarity between two vectors (arrays of numbers).
 *
 * @param array $vecA First vector.
 * @param array $vecB Second vector.
 * @return float|false Cosine similarity score (between -1 and 1), or false on error.
 */
function cosineSimilarity(array $vecA, array $vecB): float|false
{
    $countA = count($vecA);
    $countB = count($vecB);

    // Ensure vectors are non-empty and have the same dimension
    if ($countA === 0 || $countA !== $countB) {
        error_log("Cosine Similarity Error: Vectors must be non-empty and have the same dimensions.");
        return false;
    }

    $dotProduct = 0.0;
    $normA = 0.0;
    $normB = 0.0;

    for ($i = 0; $i < $countA; $i++) {
        if (!is_numeric($vecA[$i]) || !is_numeric($vecB[$i])) {
            error_log("Cosine Similarity Error: Vectors must contain only numeric values.");
            return false;
        }
        $dotProduct += $vecA[$i] * $vecB[$i];
        $normA += $vecA[$i] * $vecA[$i];
        $normB += $vecB[$i] * $vecB[$i];
    }

    $magnitude = sqrt($normA) * sqrt($normB);

    // Avoid division by zero
    if ($magnitude == 0) {
        return 0.0; // Or handle as an error/special case
    }

    return $dotProduct / $magnitude;
}
?>