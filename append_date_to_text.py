import json

INPUT_FILE = "czech_model_chunks_embed_full_12.4_converted.json"
OUTPUT_FILE = "czech_model_chunks_embed_full_12.4_converted_append_date.json"

def append_date_to_text(input_path, output_path):
    with open(input_path, "r", encoding="utf-8") as f:
        data = json.load(f)

    for obj in data:
        date = obj.get("original_article_date", "")
        text = obj.get("text", "")
        # Append date to text, separated by a newline if not empty
        if date and text:
            obj["text"] = f"{text}\n{date}"
        elif date:
            obj["text"] = date
        # If text is empty, leave as is (or just set to date)

    with open(output_path, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)

if __name__ == "__main__":
    append_date_to_text(INPUT_FILE, OUTPUT_FILE)