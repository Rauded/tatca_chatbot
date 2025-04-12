"""
tatce_scraper.py

Web scraper for extracting articles, images, and files from the Tatce.cz website.
Performs OCR on images and PDFs, and saves extracted data to JSON files.

Dependencies:
- requests
- beautifulsoup4
"""

import requests
from bs4 import BeautifulSoup
import json
import time
from urllib.parse import urljoin
# import pymupdf   # PyMuPDF (Unused import removed)
import io

# -------------------- Constants and Configuration --------------------

BASE_URL = "https://www.tatce.cz/"
START_URL = urljoin(BASE_URL, "/prakticke-info/aktuality/")
OCR_API_URL = "https://api.ocr.space/parse/image"
OCR_API_KEY = "K85857828188957"  # Optional: Add your OCR.Space API key here if you have one

HEADERS = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.212 Safari/537.36"
}

TEXT_EXTRACTION_EXTENSIONS = ('.pdf',)
IMAGE_EXTENSIONS_FOR_OCR = ('.jpg', '.jpeg', '.png', '.gif', '.bmp', '.tiff')

# -------------------- Utility Functions --------------------

def fetch_page(url, is_binary=False, timeout=20):
    """
    Fetches a web page or binary file from the given URL.

    Args:
        url (str): The URL to fetch.
        is_binary (bool): Whether to fetch as binary (for files).
        timeout (int): Request timeout in seconds.

    Returns:
        str or bytes or None: The page content (text or binary), or None on error.
    """
    try:
        response = requests.get(url, headers=HEADERS, timeout=timeout, stream=is_binary)
        response.raise_for_status()
        if is_binary:
            return response.content
        else:
            response.encoding = response.apparent_encoding
            return response.text
    except requests.exceptions.Timeout:
        print(f"Timeout error fetching {url}")
        return None
    except requests.exceptions.RequestException as e:
        print(f"Error fetching {url}: {e}")
        return None

def ocr_file_url(file_url):
    """
    Sends a file URL to the OCR.Space API and returns the extracted text.

    Args:
        file_url (str): The URL of the image or PDF to OCR.

    Returns:
        str: The extracted OCR text or an error message.
    """
    if not OCR_API_KEY or OCR_API_KEY == "K85857828188957":
        print(f"Warning: Using default/free OCR API key for {file_url}. Limits may apply.")

    payload = {
        'url': file_url,
        'isOverlayRequired': False,
        'language': 'cze',
        'detectOrientation': True,
        'apikey': OCR_API_KEY,
        'scale': True,
        'OCREngine': 1
    }
    ocr_text = f"OCR Error: No response for {file_url}"
    try:
        response = requests.post(OCR_API_URL, data=payload, timeout=45)
        response.raise_for_status()
        result = response.json()

        if result.get("IsErroredOnProcessing"):
            ocr_text = f"OCR Error: {result.get('ErrorMessage', ['Unknown OCR Error'])[0]} for {file_url}"
        elif result.get("ParsedResults"):
            # Concatenate text from all parsed pages
            extracted = []
            for page_result in result["ParsedResults"]:
                extracted.append(page_result.get("ParsedText", "").strip())
            ocr_text = "\n---\n".join(filter(None, extracted))
            if not ocr_text:
                print(f"OCR completed for {file_url}, but no text detected.")
                ocr_text = "[OCR completed, but no text detected]"
        else:
            print(f"OCR Error: Unexpected API response format for {file_url}. Response: {result}")
            ocr_text = f"OCR Error: Unexpected API response format for {file_url}. Response: {result}"

    except requests.exceptions.Timeout:
        ocr_text = f"OCR Error: Timeout processing {file_url}"
        print(ocr_text)
    except requests.exceptions.RequestException as e:
        ocr_text = f"OCR Error: Request failed for {file_url}: {e}"
        print(ocr_text)
    except json.JSONDecodeError:
        ocr_text = f"OCR Error: Could not decode JSON response for {file_url}"
        print(ocr_text)
    except Exception as e:
        ocr_text = f"OCR Error: An unexpected error occurred for {file_url}: {e}"
        print(ocr_text)

    # Respect free API rate limits
    if OCR_API_KEY == "K85857828188957":
        time.sleep(1)

    return ocr_text

def extract_text_from_pdf_url(pdf_url):
    """
    Extracts text from a PDF file by sending it to the OCR API.

    Args:
        pdf_url (str): The URL of the PDF file.

    Returns:
        str: The extracted text from the PDF.
    """
    print(f"  Sending PDF to OCR API: {pdf_url}")
    ocr_result = ocr_file_url(pdf_url)
    return ocr_result

def extract_article_links(html):
    """
    Extracts article links from the listing page HTML.

    Args:
        html (str): The HTML content of the listing page.

    Returns:
        list: List of article link URLs (relative).
    """
    soup = BeautifulSoup(html, "html.parser")
    article_container = soup.select_one("div.module_content.events.readable_list")
    if not article_container:
        print("Could not find article container on the page.")
        print("No article container found. The page structure may have changed or the selector is incorrect.")
        return []

    links = []
    for a in article_container.select("a.event-link"):
        href = a.get('href')
        if href:
            links.append(href)
    return links

def extract_article_content(article_url):
    """
    Extracts the content, images, and files from a single article page.

    Args:
        article_url (str): The full URL of the article.

    Returns:
        dict: Article data including title, date, content, OCR text, and file text.
    """
    html = fetch_page(article_url)
    if not html:
        return None

    soup = BeautifulSoup(html, "html.parser")
    article_data = {
        "url": article_url,
        "title": "N/A",
        "date": "N/A",
        "content": "N/A",
        "images_ocr_text": [],
        "files_extracted_text": []
    }

    # Main content area selection
    main_content_area = soup.select_one("#gcm-main")
    if not main_content_area:
        print(f"Warning: Could not find main content area (#gcm-main) on {article_url}")
        return article_data

    # Extract title
    title_tag = main_content_area.select_one("h1")
    if title_tag:
        article_data["title"] = title_tag.get_text(strip=True)

    # Prefer event date, avoid insertion/update dates
    date_tag = main_content_area.select_one('.event-info-value')
    if date_tag:
        article_data["date"] = date_tag.get_text(strip=True)

    # Extract main content text
    content_element = main_content_area.select_one(".module_content")
    if not content_element:
        content_element = main_content_area
        print(f"Warning: Using fallback content area (#gcm-main) for {article_url}")

    text_parts = []
    for element in content_element.find_all(string=True):
        # Exclude script/style/noscript/h1/a tags and empty strings
        if element.parent.name not in ['script', 'style', 'noscript', 'h1', 'a'] and element.strip():
            text_parts.append(element.strip())
    article_data["content"] = "\n".join(text_parts)
    if not article_data["content"] and content_element != main_content_area:
        # Fallback: get all text from main content area
        article_data["content"] = main_content_area.get_text(separator="\n", strip=True)

    # Process images for OCR (only first unique image per article)
    processed_image_urls = set()
    for img in content_element.select("img"):
        img_src = img.get('src')
        if img_src:
            absolute_img_url = urljoin(article_url, img_src)
            if absolute_img_url not in processed_image_urls:
                print(f"  Processing image for OCR: {absolute_img_url}")
                ocr_result = ocr_file_url(absolute_img_url)
                article_data["images_ocr_text"].append({
                    "image_url": absolute_img_url,
                    "ocr_text": ocr_result
                })
                processed_image_urls.add(absolute_img_url)
                # Only process the first unique image per article
                break

    # Process linked files (PDFs and images)
    processed_file_urls = set()
    for a in content_element.select("a"):
        file_href = a.get('href')
        if not file_href:
            continue

        file_href_lower = file_href.lower()
        absolute_file_url = urljoin(article_url, file_href)

        if absolute_file_url in processed_file_urls:
            continue

        file_text = a.get_text(strip=True) or absolute_file_url.split('/')[-1]
        extracted_text_info = {"file_url": absolute_file_url, "link_text": file_text, "extracted_text": None}

        if file_href_lower.endswith('.pdf'):
            # Extract text from PDF files
            pdf_text = extract_text_from_pdf_url(absolute_file_url)
            extracted_text_info["extracted_text"] = pdf_text
            article_data["files_extracted_text"].append(extracted_text_info)
            processed_file_urls.add(absolute_file_url)

        elif file_href_lower.endswith(IMAGE_EXTENSIONS_FOR_OCR):
            # OCR for linked images
            print(f"  Processing linked image for OCR: {absolute_file_url}")
            ocr_result = ocr_file_url(absolute_file_url)
            extracted_text_info["extracted_text"] = ocr_result
            extracted_text_info["link_text"] += " (Linked Image)"
            article_data["files_extracted_text"].append(extracted_text_info)
            processed_file_urls.add(absolute_file_url)

    return article_data

import os

def save_articles_to_file(articles):
    """
    Saves the list of articles to a JSON file, using a unique filename if needed.

    Args:
        articles (list): List of article data dictionaries.
    """
    base_name = "tatce_articles_with_extracted_text_retry"
    ext = ".json"
    filename = base_name + ext
    counter = 1
    # Find a filename that does not exist
    while os.path.exists(filename):
        filename = f"{base_name}_{counter}{ext}"
        counter += 1
    try:
        with open(filename, "w", encoding="utf-8") as f:
            json.dump(articles, f, ensure_ascii=False, indent=2)
        print(f"\nSaved {len(articles)} articles to {filename}.")
    except IOError as e:
        print(f"Error writing to file {filename}: {e}")

def scrape_all_articles():
    """
    Main scraping loop: paginates through article listing pages, extracts articles,
    processes images and files, and saves results to a JSON file.
    """
    all_articles = []
    page_num = 1
    processed_urls = set()

    try:
        while True:
            if page_num == 1:
                page_url = START_URL
            else:
                page_url = f"{START_URL}?page={page_num}"

            print(f"Fetching listing page: {page_url}")
            html = fetch_page(page_url)
            if not html:
                print(f"Failed to fetch page {page_num}, stopping.")
                break

            article_links = extract_article_links(html)
            if not article_links:
                if page_num == 1:
                    print("No article links found on the first page. Check selectors or page URL.")
                else:
                    print("No more article links found, stopping pagination.")
                break

            print(f"Found {len(article_links)} links on page {page_num}.")
            new_links_found = False
            for relative_url in article_links:
                article_url = urljoin(BASE_URL, relative_url)

                if article_url in processed_urls:
                    continue

                processed_urls.add(article_url)
                new_links_found = True
                print(f"Processing article: {article_url}")
                try:
                    article_data = extract_article_content(article_url)
                    if article_data:
                        all_articles.append(article_data)
                        if len(all_articles) >= 30:
                            print("Reached limit of 30 articles. Stopping scrape.")
                            raise KeyboardInterrupt
                    else:
                        print(f"Failed to extract content for {article_url}")
                    time.sleep(1)

                except Exception as e:
                    print(f"CRITICAL Error processing article {article_url}: {e}")
                # Check again after inner loop break to exit outer pagination loop
                if len(all_articles) >= 30:
                    break

            if not new_links_found and page_num > 1:
                print(f"No new unique articles found on page {page_num}. Stopping.")
                break

            page_num += 1
            print("-" * 20 + f" Moving to page {page_num} " + "-" * 20)
            time.sleep(1.5)

    except KeyboardInterrupt:
        print("\nScraper interrupted. Saving progress...")

    save_articles_to_file(all_articles)

if __name__ == "__main__":
    # Entry point: start the scraping process
    scrape_all_articles()
