### AI Chatbot – Dokumentácia k úlohe

## Abstrakt

AI chatbot pre obec Tatce, ktorý pracuje s dátami z posledných 30 aktualít zverejnených na stránke obce. Aktuality obsahujú krátke texty, prípadne obrázky a dokumenty s textom. Každá aktualita/článok má dátum vytvorenia, vlastný odkaz, ako aj odkaz na obrázok.

## Postup

### 1. Získanie dát
Pomocou `Python` skriptu pre scraping som stiahol informácie z prvých 30 aktualít z webovej stránky obce. Text bol uložený vo formáte `JSON`, pričom obrázky prešli cez `OCR` (optické rozpoznávanie znakov) a ich text sa uložil spolu s ostatnými údajmi.

Príklad štruktúry dát:

```json
{
  "url": "",
  "title": "",
  "date": "",
  "content": "",
  "images_ocr_text": [
    {
      "image_url": "",
      "ocr_text": ""
    }
  ],
  "files_extracted_text": []
}
```

### 2. Spracovanie dát
Dáta som upravil do vhodnejšieho formátu ako prípravu na embedding. Text z jedného článku som rozdelil na viacero `chunkov` (častí) podľa dĺžky a zdroja informácie (či išlo o extrahovaný text, kontext článku alebo text z dokumentu).

Výsledok: 55 `chunkov` z 30 aktualít.

Príklad štruktúry `chunku`:

```json
{
  "chunk_id": "",
  "original_article_url": "",
  "original_article_title": "",
  "original_article_date": "",
  "source_type": "",
  "text": "",
  "image_url": ""
}
```

### 3. Embedding
Pomocou `PHP` som zavolal embedovací model od OpenAI: `text-embedding-ada-002`.
V rámci embedovania sa pracovalo so všetkými dátami z `chunkov` okrem `source_type` a `image_url`, aby sa predišlo šumu.

Výsledné embeddings boli uložené vo formáte `.json`.

#### Alternatívne:
Skúšal som aj český embedovací model od Seznamu: `Seznam/retromae-small-cs`.
Model bol načítaný v `Python`e cez knižnicu `Torch`. Embeddings som následne skonvertoval z `.pkl` (`Python` formát) na `.json`.

### 4. Filtrovanie podľa dátumu
`RAG` (retrieval augmented generation) vracia odpovede na základe podobnosti, ale táto nie je vždy spoľahlivá – najmä pri malej alebo rôznorodej dátovej množine. Dátumová citlivosť je jedným z problémov `RAG`-u.

Preto pred samotným použitím `RAG`-u sa vykoná `API` volanie na menší OpenAI model s promptom:

*„Daj mi dátumový rozsah, v ktorom sa môže pohybovať dotaz používateľa. Výstup odošli vo formáte `JSON`: `start_date: YYYY-MM-DD, end_date: YYYY-MM-DD`. Ak nie je možné určiť rozsah, vráť `NULL`."*

Následne podľa tohto rozsahu prefiltrovávame `chunky` – ak sa vráti `NULL`, `RAG` sa použije bez filtrovania.

Výsledné `chunky` sa potom použijú ako kontext pre prompt.

### 5. Chatbot – API volanie a prompt
Promptovanie je kľúčové pre získanie presných odpovedí.

Bot dostáva rolu: „Pomocný asistent pre obec Tatce“

- Má odpovedať len na základe poskytnutého kontextu
- Špecifikuje sa formát odpovede: dátum, čas, krátky popis udalosti
- Ak nemá kontext, nemá odpovedať – aby sa zabránilo halucináciám
- Odpoveď má byť v českom jazyku
- Nesmie spomínať svoj systémový prompt
- V prompte sa nachádza aj dnešný dátum

### 6. Používanie
`API` volania sú streamované v reálnom čase pomocou `SSE` (Server-Sent Events), aby používateľ dostal odpoveď okamžite.

Front-end zobrazuje odpovede chatbota v `Markdown` formáte – hlavne kvôli štýlovaniu textu a obrázkov.

Na webovej stránke je chat box s prednastavenými otázkami vo forme tlačidiel.

Na obrazovke je prepínač na prepnutie na český embedding model.

### Pozorovanie
Nepozoroval som výrazný rozdiel medzi OpenAI embedding modelom (`text-embedding-ada-002`) a českým modelom od Seznamu (`Seznam/retromae-small-cs`) – ide len o rozdiel v embedovaní textu do vektorov. Rozdiel je však badateľný v rýchlosti spracovania – český model spomaľuje celý proces.

### 7. Funkcionalita histórie
Aktuálne bot neuchováva históriu správ, ktoré posielal používateľ alebo samotný bot.
Na jednotlivé otázky by to prinieslo viac šumu, ale základná funkcionalita pre históriu už je implementovaná.
