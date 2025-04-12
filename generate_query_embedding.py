import sys
import json
import torch
from transformers import AutoModel, AutoTokenizer
import numpy as np

# --- Configuration ---
MODEL_NAME = "Seznam/retromae-small-cs"  # Use the same model as for chunk embeddings
MAX_LENGTH = 512

def main():
    # Read query from stdin or command-line argument
    if len(sys.argv) > 1:
        query = " ".join(sys.argv[1:])
    else:
        query = sys.stdin.read().strip()
    if not query:
        print(json.dumps({"error": "No query provided"}))
        sys.exit(1)

    # Device selection
    device = torch.device("cuda" if torch.cuda.is_available() else "cpu")

    # Load model and tokenizer
    tokenizer = AutoTokenizer.from_pretrained(MODEL_NAME)
    model = AutoModel.from_pretrained(MODEL_NAME)
    model.to(device)
    model.eval()

    # Tokenize and encode
    with torch.no_grad():
        batch_dict = tokenizer(
            [query],
            max_length=MAX_LENGTH,
            padding=True,
            truncation=True,
            return_tensors='pt'
        )
        batch_dict = {k: v.to(device) for k, v in batch_dict.items()}
        outputs = model(**batch_dict)
        embedding = outputs.last_hidden_state[:, 0].cpu().numpy()[0]  # [CLS] token

    # Convert to list for JSON serialization
    embedding_list = embedding.tolist()
    print(json.dumps({"embedding": embedding_list}))

if __name__ == "__main__":
    main()