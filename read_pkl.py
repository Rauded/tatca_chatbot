import pickle

# Path to the .pkl file
pkl_file = "czech_model_chunks_embed_full.pkl"

# Load the .pkl file
with open(pkl_file, "rb") as f:
    data = pickle.load(f)

# Print the type and a preview of the loaded data
print(f"Loaded object type: {type(data)}")
if isinstance(data, dict):
    print(f"Dictionary keys (up to 10): {list(data.keys())[:10]}")
elif isinstance(data, list):
    print(f"List length: {len(data)}")
    print(f"First 3 items: {data[:55]}")
else:
    print(f"Object preview: {str(data)[:500]}")