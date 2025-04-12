import pickle
import json
import numpy as np

# Path to the .pkl file
pkl_file = "czech_model_chunks_embed_full_12.4.pkl"
json_file = "czech_model_chunks_embed_full_12.4.json"

# Load the .pkl file
with open(pkl_file, "rb") as f:
    data = pickle.load(f)

# Convert numpy arrays to lists for JSON serialization
for item in data:
    if isinstance(item, dict) and "embedding" in item and isinstance(item["embedding"], np.ndarray):
        item["embedding"] = item["embedding"].tolist()

# Save to JSON
with open(json_file, "w", encoding="utf-8") as f:
    json.dump(data, f, ensure_ascii=False, indent=2)

print(f"Converted {pkl_file} to {json_file}")