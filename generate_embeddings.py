import torch
from transformers import AutoModel, AutoTokenizer
import json
import pickle
import numpy as np
import os
import sys
from tqdm import tqdm # For progress bar

# --- Configuration ---
MODEL_NAME = "Seznam/retromae-small-cs"
INPUT_JSON_FILE = "chunks_12.4.json"
OUTPUT_PICKLE_FILE = "czech_model_chunks_embed_full_12.4.pkl" # Added .pkl extension
BATCH_SIZE = 32 # Increased batch size slightly, adjust based on your GPU memory if using GPU
MAX_LENGTH = 512 # Max sequence length for the model

# --- Device Selection (GPU if available, otherwise CPU) ---
if torch.cuda.is_available():
    device = torch.device("cuda")
    print(f"Using GPU: {torch.cuda.get_device_name(0)}")
else:
    device = torch.device("cpu")
    print("Using CPU")

# --- Load Model and Tokenizer ---
try:
    print(f"Loading tokenizer: {MODEL_NAME}...")
    tokenizer = AutoTokenizer.from_pretrained(MODEL_NAME)
    print(f"Loading model: {MODEL_NAME}...")
    model = AutoModel.from_pretrained(MODEL_NAME)
    model.to(device) # Move model to the selected device
    model.eval()    # Set model to evaluation mode (disables dropout, etc.)
    print("Model and tokenizer loaded successfully.")
except Exception as e:
    print(f"Error loading model or tokenizer: {e}")
    sys.exit() # Exit if model/tokenizer can't be loaded

# --- Load Chunks from JSON ---
if not os.path.exists(INPUT_JSON_FILE):
    print(f"Error: Input JSON file not found at {INPUT_JSON_FILE}")
    sys.exit()

try:
    print(f"Loading chunks from {INPUT_JSON_FILE}...")
    with open(INPUT_JSON_FILE, "r", encoding="utf-8") as f:
        chunks_data = json.load(f)
    print(f"Loaded {len(chunks_data)} chunks.")
except json.JSONDecodeError as e:
    print(f"Error decoding JSON from {INPUT_JSON_FILE}: {e}")
    sys.exit()
except Exception as e:
    print(f"An unexpected error occurred while reading {INPUT_JSON_FILE}: {e}")
    sys.exit()

# --- Extract Texts and Chunk IDs (with basic validation) ---
chunk_ids = []
texts = []
valid_chunks = []
malformed_chunks = 0
empty_text_chunks = 0
for i, chunk in enumerate(chunks_data):
    if isinstance(chunk, dict) and "chunk_id" in chunk and "text" in chunk:
        # Concatenate fields for embedding: original_article_url, original_article_title, original_article_date, source_type, text
        fields = [
            chunk.get("original_article_url", ""),
            chunk.get("original_article_title", ""),
            chunk.get("original_article_date", ""),
            chunk.get("source_type", ""),
            chunk.get("text", "")
        ]
        # Remove empty/whitespace-only fields and join with ' | '
        concat_text = " | ".join([str(f).strip() for f in fields if str(f).strip() != ""])
        if concat_text:
            chunk_ids.append(chunk["chunk_id"])
            texts.append(concat_text)
            valid_chunks.append(chunk)
        else:
            empty_text_chunks += 1
            print(f"Warning: Skipping chunk with empty or whitespace-only text at index {i}: {chunk}")
    else:
        malformed_chunks += 1
        print(f"Warning: Skipping malformed chunk at index {i}: {chunk}")

if malformed_chunks > 0:
    print(f"Warning: Skipped {malformed_chunks} malformed chunks.")
if empty_text_chunks > 0:
    print(f"Warning: Skipped {empty_text_chunks} chunks with empty or whitespace-only text.")

if not texts:
    print("Error: No valid text chunks found in the input file.")
    sys.exit()

# --- Remove duplicate chunk_ids (keep first occurrence) ---
seen_ids = set()
unique_chunk_ids = []
unique_texts = []
unique_chunks = []
duplicate_chunks = 0
for cid, text, chunk in zip(chunk_ids, texts, valid_chunks):
    if cid not in seen_ids:
        seen_ids.add(cid)
        unique_chunk_ids.append(cid)
        unique_texts.append(text)
        unique_chunks.append(chunk)
    else:
        duplicate_chunks += 1
        print(f"Warning: Skipping duplicate chunk_id '{cid}'")

if duplicate_chunks > 0:
    print(f"Warning: Skipped {duplicate_chunks} duplicate chunk_ids.")

chunk_ids = unique_chunk_ids
texts = unique_texts
valid_chunks = unique_chunks

# --- Generate Embeddings in Batches ---
print("Generating embeddings...")
all_embeddings_data = []

# Use torch.no_grad() to disable gradient calculations, saving memory and computation
with torch.no_grad():
    # Wrap the loop with tqdm for a progress bar
    for i in tqdm(range(0, len(texts), BATCH_SIZE), desc="Processing batches", ascii=True):
        batch_texts = texts[i:i+BATCH_SIZE]
        batch_ids = chunk_ids[i:i+BATCH_SIZE]

        # Tokenize the batch
        batch_dict = tokenizer(
            batch_texts,
            max_length=MAX_LENGTH,
            padding=True,
            truncation=True,
            return_tensors='pt' # Return PyTorch tensors
        )

        # Move tokenized batch to the same device as the model
        batch_dict = {k: v.to(device) for k, v in batch_dict.items()}

        # Get model outputs
        outputs = model(**batch_dict)

        # Extract embeddings (using the [CLS] token's hidden state)
        # outputs.last_hidden_state shape: (batch_size, sequence_length, hidden_size)
        # We take the hidden state corresponding to the first token ([CLS]) -> shape: (batch_size, hidden_size)
        batch_embeds = outputs.last_hidden_state[:, 0]

        # Move embeddings to CPU and convert to NumPy arrays (necessary for pickling)
        batch_embeds_cpu = batch_embeds.cpu().numpy()

        # Store embeddings with their corresponding chunk IDs
        for chunk, emb in zip(valid_chunks[i:i+BATCH_SIZE], batch_embeds_cpu):
            chunk_with_embedding = dict(chunk)  # Copy original chunk dict
            chunk_with_embedding["embedding"] = emb
            # Ensure image_url is always present (set to None if missing)
            chunk_with_embedding["image_url"] = chunk.get("image_url", None)
            all_embeddings_data.append(chunk_with_embedding)

# --- Save Embeddings using Pickle ---
try:
    print(f"Saving {len(all_embeddings_data)} embeddings to {OUTPUT_PICKLE_FILE}...")
    with open(OUTPUT_PICKLE_FILE, "wb") as f_out:
        pickle.dump(all_embeddings_data, f_out)
    print("Embeddings saved successfully.")
except IOError as e:
    print(f"Error saving embeddings to {OUTPUT_PICKLE_FILE}: {e}")
except Exception as e:
     print(f"An unexpected error occurred during saving: {e}")

# --- Summary ---
print("\n--- Processing Summary ---")
print(f"Embeddings saved: {len(all_embeddings_data)}")
print(f"Malformed chunks skipped: {malformed_chunks}")
print(f"Chunks with empty/whitespace-only text skipped: {empty_text_chunks}")
print(f"Duplicate chunk_ids skipped: {duplicate_chunks}")