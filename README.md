# CMS GPT Website

[![PHP](https://img.shields.io/badge/PHP-8.0+-purple.svg)](https://www.php.net/)
[![Python](https://img.shields.io/badge/Python-3.8+-blue.svg)](https://www.python.org/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

A CMS-style web application with GPT integration for document processing and translation memory management.

## Features

- **User Management** — Registration and login system
- **Multi-format Upload** — DOCX, TMX, and TXT file support
- **GPT Integration** — Python backend (`main.py`) for AI-powered text processing
- **Similar Sentence Detection** — Find and analyze similar sentences across documents
- **Translation Memory (TMX)** — Import and manage TMX files

## Tech Stack

- **Frontend**: PHP
- **Backend**: Python (GPT processing)
- **Database**: MySQL (via `shared/conn.php`)

## Installation

1. Place the project folder in your web server directory (e.g., `htdocs` or `www`).
2. Import the database schema and configure `shared/conn.php`.
3. Install Python dependencies:
   ```bash
   pip install -r requirements.txt
   ```
4. Access `index.php` via your web browser.

## File Structure

```
├── index.php              # Main page
├── login.php / register.php
├── main.py                # GPT processing backend
├── upload_docx.php
├── upload_tmx.php
├── upload_txt.php
├── docx.php / tmx.php / txt.php
├── find_similar_sentences.php
└── shared/conn.php        # Database connection
```

## License

[MIT](LICENSE)
