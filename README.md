<p align="center">
  <img src="docs/assets/img/logo.svg" width="200" height="200" alt="Curly CMS Logo">
</p>

# Curly CMS: The AI-Native Agentic Content Management System

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.0-8892bf.svg)](https://php.net)
[![Stability](https://img.shields.io/badge/stability-pre--alpha-red.svg)](https://github.com/RHEINMAINTECH/Curly)

**Curly CMS** is a professional, open-source content management system built for the age of artificial intelligence. It was designed as a modular, extensible counterproposal to proprietary platforms like EmDash, prioritizing developer freedom, agentic interoperability, and security.

> [!IMPORTANT]
> **Project Status: Pre-Alpha**. This project is in very early development. APIs and database schemas are subject to frequent changes.

---

## 🚀 Key Features

- **AI-Native Core:** Deep integration with OpenAI, Anthropic Claude, and local Ollama instances.
- **Agentic Interoperability:** Native support for **A2A (Agent-to-Agent)** messaging and **MCS (Model Context Server)** protocols for multi-agent workflows.
- **Secure Sandbox:** A hardened execution environment for extensions, restricting file system and system access while allowing safe PHP execution.
- **JSON-Based Layouts:** All page structures are stored as machine-readable JSON, enabling AI agents to design and reconfigure UI components dynamically.
- **Bootstrap 5 UI:** Built-in component library using the industry-standard Bootstrap 5 framework.
- **Open-Source Freedom:** Fully self-hosted, MIT-licensed, and designed to be the extensible alternative to locked-down proprietary CMS offerings.

---

## 🛠 Architecture

### The Secure Sandbox
Curly CMS solves the "untrusted code" problem by wrapping all extensions in a `CurlyCMS\Core\Sandbox`. Extensions have zero access to `exec`, `system`, or raw file writing. They interact with the core through a strictly governed `SandboxAPI`.

### Machine-Readable Design
Pages are structured as hierarchical JSON trees:
{
    "type": "container",
    "children": [
        {
            "type": "row",
            "children": [
                {
                    "type": "column",
                    "cols": 8,
                    "children": [{"type": "heading", "content": "AI-Generated Layout"}]
                }
            ]
        }
    ]
}

---

## 📦 Installation

### Prerequisites
- PHP 8.0 or higher
- SQLite (default) or MySQL/PostgreSQL
- OpenSSL & cURL extensions

### Step-by-Step
1. **Clone the repository**
   git clone https://github.com/RHEINMAINTECH/Curly.git
   cd Curly

2. **Configure Environment**
   cp .env.example .env
   # Edit .env with your database and AI API keys

3. **Run Installer**
   php install/install.php

4. **Serve the Application**
   Point your web server's document root to the `public` directory.

---

## 🤖 AI Protocols

### A2A (Agent-to-Agent)
Allows external AI agents to communicate directly with Curly CMS to perform tasks:
POST /api/a2a/task
Authorization: Bearer <TOKEN>
Content-Type: application/json

{
  "task_type": "generate_post",
  "params": {
    "title": "The Future of Agentic Web",
    "prompt": "Write a deep dive into A2A protocols..."
  }
}

### MCS (Model Context Server)
Provides a standardized way for LLMs to query the "context" of your website, including settings, page structures, and content schemas.

---

## 🧩 Extensions
Extensions live in `/extensions`. Each requires a `manifest.json`. Curly CMS automatically discovers these and allows activation via the admin dashboard.

---

## 🛡 Security
- **Password Hashing:** Argon2ID (industry standard).
- **CSRF Protection:** Native on all state-changing requests.
- **XSS Prevention:** Automatic output escaping via the View engine.
- **Extension Isolation:** No unvetted code runs with system privileges.

## 📄 License
Curly CMS is open-source software licensed under the [MIT license](LICENSE).

---

## 🤝 Contributing
We welcome contributions! Please see our contributing guidelines for more information.

Developed by **RheinMainTech GmbH**. Built for the Agentic Web.