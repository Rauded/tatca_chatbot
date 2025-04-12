import json
from datetime import datetime

INPUT_FILE = "czech_model_chunks_embed_full_12.4.json"
OUTPUT_FILE = "czech_model_chunks_embed_full_12.4_converted.json"

def convert_date(date_str):
    # Example input: "7. 4. 2025 10:06"
    try:
        dt = datetime.strptime(date_str, "%d. %m. %Y %H:%M")
        return dt.strftime("%Y-%m-%d")
    except Exception:
        # If parsing fails, return the original string
        return date_str

def main():
    with open(INPUT_FILE, "r", encoding="utf-8") as f:
        data = json.load(f)

    for item in data:
        if "original_article_date" in item:
            item["original_article_date"] = convert_date(item["original_article_date"])

    with open(OUTPUT_FILE, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)

if __name__ == "__main__":
    main()