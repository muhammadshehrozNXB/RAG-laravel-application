# RAG Laravel

A Laravel 9 application that demonstrates Retrieval-Augmented Generation (RAG) and Text-to-SQL patterns on top of a MySQL database, using Anthropic's Claude for generation.

## Features

### Document ingestion
- Upload `.txt`, `.md`, or `.pdf` files, or paste raw text directly, as source documents.
- Ingest the results of a live SQL query (against the app's own database or an external MySQL connection) as a document — useful for turning structured data into retrievable knowledge.
- PDF text extraction via `smalot/pdfparser`.
- Automatic sentence-aware chunking with configurable chunk size and overlap (`App\Services\DocumentService`).
- A sample "AI overview" PDF can be generated and downloaded on demand (via `setasign/fpdf`) for quickly testing the ingestion pipeline.
- Document list, detail view (with per-chunk breakdown), and delete.

### RAG chat (`/chat`)
- Ask natural-language questions against the ingested document corpus.
- Retrieval is powered by MySQL FULLTEXT search (`MATCH ... AGAINST ... IN BOOLEAN MODE`) over document chunks, ranked by relevance score, with a recency-based fallback when there are no full-text matches.
- The top-K retrieved chunks are assembled into a context block and passed to Claude, which is instructed to answer only from the provided context.
- Responses include the generated answer plus the source chunks/documents used, each with an excerpt and relevance score.

### Database chat / Text-to-SQL (`/db-chat`)
- Point the app at any MySQL database (host, port, database, credentials) and ask questions in plain English.
- The app discovers the target database's schema (tables, columns, types, keys), asks Claude to generate a single `SELECT` query answering the question, executes it, and asks Claude to summarize the results in plain English.
- If the generated query fails, the app feeds the error back to Claude for one self-correction attempt before giving up.
- Only `SELECT` statements are allowed — a query guard blocks `DROP`, `DELETE`, `UPDATE`, `INSERT`, `ALTER`, `CREATE`, `TRUNCATE`, `REPLACE`, and `EXEC` in both the ingestion path and the generated-SQL path.
- A "test connection" endpoint lists the target database's tables before running a query.

## Tech stack

- **Framework:** Laravel 9 (PHP ^8.0.2)
- **Database:** MySQL (with a FULLTEXT index on document chunks for retrieval)
- **LLM:** Anthropic Claude (`App\Services\AnthropicService`) for answer generation, SQL generation, and result summarization
- **Embeddings/OpenAI (optional):** `App\Services\OpenAIService` wraps OpenAI's embeddings and chat completion APIs, available for embedding-based retrieval if needed
- **PDF handling:** `smalot/pdfparser` (read) and `setasign/fpdf` (generate sample document)

## Setup

1. Install dependencies:
   ```bash
   composer install
   npm install
   ```
2. Copy `.env.example` to `.env` and configure your database connection, then generate an app key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
3. Add your LLM API keys to `.env` (used by `config/anthropic.php` and `config/openai.php`):
   ```
   ANTHROPIC_API_KEY=
   ANTHROPIC_MODEL=claude-haiku-4-5-20251001

   OPENAI_API_KEY=
   OPENAI_EMBEDDING_MODEL=text-embedding-3-small
   OPENAI_CHAT_MODEL=gpt-4o-mini
   ```
4. Run migrations:
   ```bash
   php artisan migrate
   ```
5. Serve the app:
   ```bash
   php artisan serve
   ```

## Routes

| Path                      | Description                                   |
|---------------------------|------------------------------------------------|
| `/documents`               | List, upload, view, and delete documents      |
| `/documents/sample/download` | Download a sample PDF for testing ingestion |
| `/chat`                    | Ask questions against ingested documents (RAG)|
| `/db-chat`                 | Ask natural-language questions against a MySQL database (Text-to-SQL) |
