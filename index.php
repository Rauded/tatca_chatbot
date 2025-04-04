<?php

// --- Configuration ---
// IMPORTANT: Set your OpenAI API Key as an environment variable 'OPENAI_API_KEY'
//            DO NOT HARDCODE YOUR KEY HERE IN PRODUCTION.
$apiKey = $_ENV['OPENAI_API_KEY'];

// Fallback for testing (REMOVE THIS IN PRODUCTION)
// if (!$apiKey) {
//     $apiKey = 'YOUR_API_KEY_HERE'; // Replace ONLY for local testing if env var isn't set
// }

if (!$apiKey) {
    // Send an error response if the API key is missing
    header('Content-Type: application/json');
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'OpenAI API key is not configured.']);
    exit; // Stop script execution
}

$apiUrl = 'https://api.openai.com/v1/chat/completions';
$model = 'gpt-4o-mini'; // Or use 'gpt-4', 'gpt-4-turbo', etc. if you have access

// --- Input ---
// Get the user's message. In a real app, this would likely come from a POST request
// Example: $userMessage = isset($_POST['message']) ? trim($_POST['message']) : '';
$userMessage = "Tell me about the main public transportation options in a typical large city."; // Example prompt

// Basic validation
if (empty($userMessage)) {
    header('Content-Type: application/json');
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Message content cannot be empty.']);
    exit;
}


// --- Prepare API Request Data ---
// Structure the data according to the OpenAI API documentation
// The 'messages' array holds the conversation history.
$data = [
    'model' => $model,
    'messages' => [
        [
            'role' => 'system', // Optional: System message to guide the AI's behavior
            'content' => 'Si pomocny informacny assistent pre obec Tarca.'
        ],
        [
            'role' => 'user',
            'content' => $userMessage
        ]
    ],
    // Optional parameters:
    // 'max_tokens' => 150, // Limit the response length
    // 'temperature' => 0.7, // Controls randomness (0 = deterministic, 1 = more random)
];

$jsonData = json_encode($data);

// --- Make the API Call using cURL ---
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string
curl_setopt($ch, CURLOPT_POST, true);           // Set method to POST
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData); // Set the JSON data as the request body
curl_setopt($ch, CURLOPT_HTTPHEADER, [          // Set necessary headers
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey,
    'Content-Length: ' . strlen($jsonData)
]);
// Optional: Set a timeout
curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 60 seconds timeout

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get HTTP status code

// --- Handle Potential cURL Errors ---
if (curl_errno($ch)) {
    $curlError = curl_error($ch);
    curl_close($ch);
    // Send an error response
    header('Content-Type: application/json');
    http_response_code(5002); // Internal Server Error
    echo json_encode(['error' => 'cURL Error: ' . $curlError]);
    exit;
}

curl_close($ch);

// --- Process the API Response ---
$responseData = json_decode($response, true); // Decode JSON response into an associative array

header('Content-Type: application/json'); // Set header before any output

if ($httpCode !== 200 || isset($responseData['error'])) {
    // Handle API errors or non-200 HTTP status codes
    http_response_code($httpCode); // Use the status code from OpenAI if available
    // Try to extract a specific error message from OpenAI's response
    $errorMessage = isset($responseData['error']['message']) ? $responseData['error']['message'] : 'Unknown API error.';
     // Include the full response for debugging if needed, but be careful not to expose sensitive info
    echo json_encode(['error' => $errorMessage, 'details' => ($httpCode !== 200 ? 'HTTP Status Code: ' . $httpCode : '') /* , 'raw_response' => $responseData */ ]);
} elseif (isset($responseData['choices'][0]['message']['content'])) {
    // Success: Extract the AI's reply
    $aiResponse = trim($responseData['choices'][0]['message']['content']);
    http_response_code(200); // OK
    echo json_encode(['reply' => $aiResponse]);
} else {
    // Handle unexpected response format
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Could not parse AI response.', 'raw_response' => $responseData]);
}

?>