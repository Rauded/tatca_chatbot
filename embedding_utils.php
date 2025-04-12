<?php
/* ============================================================================
   embedding_utils.php
   Utility functions for vector operations (e.g., cosine similarity).
   ============================================================================
*/
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

    $dotProduct = 0.0; // Accumulates the sum of products of corresponding elements
    $normA = 0.0;      // Accumulates the sum of squares for vector A
    $normB = 0.0;      // Accumulates the sum of squares for vector B

    // Iterate through each dimension of the vectors
    for ($i = 0; $i < $countA; $i++) {
        // Validate that both elements are numeric
        if (!is_numeric($vecA[$i]) || !is_numeric($vecB[$i])) {
            error_log("Cosine Similarity Error: Vectors must contain only numeric values.");
            return false;
        }
        $dotProduct += $vecA[$i] * $vecB[$i];   // Product of corresponding elements
        $normA += $vecA[$i] * $vecA[$i];        // Square of element in A
        $normB += $vecB[$i] * $vecB[$i];        // Square of element in B
    }

    // Calculate the product of the magnitudes (Euclidean norms) of the vectors
    $magnitude = sqrt($normA) * sqrt($normB);

    // Avoid division by zero (if either vector is zero)
    if ($magnitude == 0) {
        return 0.0; // Return 0 similarity for zero vectors
    }

    // Return the cosine similarity score (between -1 and 1)
    return $dotProduct / $magnitude;
}
?>