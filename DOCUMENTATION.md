# Tatce Chatbot Documentation

## Overview

This project implements a Retrieval-Augmented Generation (RAG) chatbot for the municipality of Tatce. It consists of a backend API (`api.php`) and a frontend web interface (`index.html`). The chatbot answers user queries using OpenAI's language models and a local knowledge base, with support for Czech language and date-based filtering.

---

## 1. `api.php` — Backend API

### Purpose

`api.php` serves as the main API endpoint for the chatbot. It processes user queries, extracts relevant date ranges, generates embeddings (using either a Python script or OpenAI API), retrieves relevant knowledge base chunks, and streams responses from OpenAI's chat models to the frontend.

### Key Features

- **Session Management:** Tracks user message history via PHP sessions.
- **Date Extraction:** Uses OpenAI to extract date ranges from user queries (in Czech).
- **Embedding Generation:** Supports both a local Python embedding model and OpenAI's embedding API.
- **Knowledge Base Search:** Finds relevant text chunks based on semantic similarity and date filters.
- **Streaming Responses:** Streams OpenAI chat completions to the frontend using Server-Sent Events (SSE).
- **Request Logging:** Logs API requests for debugging and analytics.

### API Endpoint

- **URL:** `api.php`
- **Method:** `POST`
- **Request Body (JSON):**
  - `prompt` (string, required): The user's question.
  - `use_python_embedding` (boolean, optional): Whether to use the local Python embedding model.

#### Example Request

```json
{
  "prompt": "Jaké jsou aktuality z minulého měsíce?",
  "use_python_embedding": true
}
```

### Response

- **Content-Type:** `text/event-stream`
- **Streaming:** The response is streamed as SSE, with each chunk containing a part of the bot's reply.

#### Example Streamed Chunk

```
data: {"choices":[{"delta":{"content":"Dobrý den, zde jsou aktuality..."}}]}
```

### Main Processing Steps

1. **Session Initialization:** Ensures message history is tracked.
2. **API Key Loading:** Loads OpenAI API key from environment.
3. **Input Parsing:** Reads and validates the JSON request.
4. **Date Extraction:** Calls OpenAI to extract `start_date` and `end_date` from the prompt.
5. **Embedding Generation:** 
   - If `use_python_embedding` is true, runs a Python script to generate the embedding.
   - Otherwise, uses OpenAI's embedding API.
6. **Knowledge Base Loading:** Loads relevant chunks from a JSON file.
7. **Chunk Filtering:**
   - Filters by date range if available.
   - Ranks by cosine similarity to the query embedding.
   - Selects the top N most relevant chunks.
8. **Prompt Construction:** Builds a system prompt and user prompt with context for the LLM.
9. **OpenAI Chat Call:** Streams the response from OpenAI's chat model to the client.
10. **Logging:** Logs the request and response details.

### Configuration

- **Environment Variables:** (via `.env`)
  - `OPENAI_API_KEY`: Your OpenAI API key.
- **Knowledge Base Files:** 
  - `czech_model_chunks_embed_full_12.4_converted_append_date.json` (for Python embedding)
  - `chunks_with_embeddings_12.4_added_text_and_image.json` (for OpenAI embedding)
- **Python Script:** `generate_query_embedding.py` (for local embedding)

---

## 2. `index.html` — Frontend Web Interface

### Purpose

`index.html` provides the user interface for the chatbot. It allows users to enter questions, select example prompts, toggle the embedding model, and view streamed responses from the backend.

### Key Features

- **Chat UI:** Displays conversation history with user and bot messages.
- **Example Prompts:** Quick-access buttons for common questions.
- **Embedding Toggle:** Switch to use a Czech-trained embedding model (via Python).
- **Streaming Responses:** Displays bot replies in real-time as they are streamed from the backend.
- **Markdown Support:** Renders bot replies with Markdown (using Marked.js), including automatic image embedding for image URLs.
- **Auto-Resizing Input:** Textarea grows with user input.
- **Sessionless:** All state is managed in the browser; no login required.

### Main UI Elements

- **Chat Container:** Displays all messages.
- **Input Area:** Textarea for user input and a send button.
- **Example Prompts:** Buttons for quick queries.
- **Embedding Toggle:** Checkbox to switch embedding models.

### JavaScript Logic

- **Message Appending:** Adds user and bot messages to the chat container, rendering Markdown and images.
- **API Communication:** Sends user input to `api.php` via `fetch` (POST), including the embedding toggle state.
- **Streaming Handling:** Processes SSE from the backend, updating the bot's message as new chunks arrive.
- **Example Prompts:** Clicking a prompt fills the textarea and sends the message.
- **Auto-Scroll:** Scrolls to the latest message as new content arrives.
- **Input Handling:** Pressing Enter sends the message; input area resizes automatically.

---

## 3. Interaction Flow

1. **User enters a question** (or clicks an example prompt) in the chat interface.
2. **Frontend sends a POST request** to `api.php` with the question and embedding toggle state.
3. **Backend processes the request:**
   - Extracts date range.
   - Generates query embedding.
   - Finds relevant knowledge base chunks.
   - Constructs a prompt for OpenAI.
   - Streams the response back to the frontend.
4. **Frontend displays the streamed response** in real-time, rendering Markdown and images.

---

## 4. File Relationships

- `index.html` (frontend) communicates directly with `api.php` (backend) via AJAX (fetch).
- `api.php` may call `generate_query_embedding.py` for local embeddings.
- Knowledge base files are loaded by `api.php` for context retrieval.

---

## 5. Customization & Extension

- **Add new example prompts** in the `index.html` file.
- **Change knowledge base** by updating the JSON files referenced in `api.php`.
- **Switch embedding models** by toggling the checkbox in the UI.
- **Modify system prompt** in `api.php` to adjust the chatbot's behavior.

---

## 6. Requirements

- **Backend:** PHP 7.4+, Composer dependencies, Python 3.x (if using local embedding), OpenAI API key.
- **Frontend:** Modern web browser, internet access for Marked.js CDN.

---

## 7. Troubleshooting

- **API errors:** Check the browser console and `api_requests.log` for details.
- **Embedding issues:** Ensure Python and required scripts are available if using the local model.
- **Knowledge base loading errors:** Verify the JSON files exist and are valid.

---