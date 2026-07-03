# CMS GPT Website

<!-- bilingual-readme:start -->

## 双语说明 / Bilingual Documentation

> 本节提供整篇 README 的中英双语维护说明；下方保留原始详细说明、命令、路径和配置示例。
> This section provides bilingual maintenance notes for the full README; the original detailed notes, commands, paths, and configuration examples are preserved below.

### 中文

**概览**：带 GPT 类 AI 集成能力的 CMS 网站项目，包含 PHP 与 Python 相关组件。

**主要能力**：
- 提供 CMS 网站基础结构。
- 集成 GPT-like AI 功能。
- 适合作为早期 Web/AI 集成项目参考。

**使用方式**：按下方依赖、环境和启动说明部署。

**状态**：该仓库仍按当前 README 的说明维护或使用。

**注意事项**：具体 PHP/Python 版本、目录和运行方式以下方原说明为准。

### English

**Overview**: CMS website project with GPT-like AI integration, including PHP and Python components.

**Key capabilities**:
- Provides a CMS website foundation.
- Integrates GPT-like AI functionality.
- Useful as an early web/AI integration reference.

**Usage**: Deploy according to the dependency, environment, and startup notes below.

**Status**: This repository is maintained or used according to the current README notes.

**Notes**: Exact PHP/Python versions, directories, and run steps follow the original details below.

<!-- bilingual-readme:end -->

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