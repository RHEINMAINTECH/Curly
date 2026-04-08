
<p align="center">
  <img src="docs/assets/img/logo.svg" width="200" height="200" alt="Curly CMS Logo">
</p>

# Curly: The AI-Native Agentic CMS

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.0-8892bf.svg)](https://php.net)
[![Stability](https://img.shields.io/badge/stability-pre--alpha-red.svg)](https://github.com/RHEINMAINTECH/Curly)

**Curly** is an AI-native Content Management System specifically designed for the era of autonomous agents and decentralized intelligence. 

It serves as the **low-threshold PHP alternative to Cloudflare's EmDash**. While EmDash utilizes a modern but complex `npm`-based stack, Curly remains within the PHP ecosystem. This allows developers and agencies to deploy an agentic-ready CMS on standard web hosting environments without the overhead of a Node.js/npm build pipeline, while retaining full power over AI integrations.

> [!IMPORTANT]
> **Project Status: Pre-Alpha**. This project is in its earliest stages of development. APIs, protocol implementations, and database schemas are subject to frequent and breaking changes.

---

## 🚀 Key Features

- **PHP-Native (Low-Threshold):** Fast deployment on any standard server. No complex Node.js/npm build-stack required for core operations.
- **Agentic-Ready (A2A):** Native support for the **Agent-to-Agent** protocol, enabling seamless communication between different AI instances.
- **Model Context (MCS):** Full implementation of the **Model Context Server** protocol, providing LLMs with structured access to site data, settings, and layouts.
- **Secure Extension Sandbox:** A hardened execution environment for third-party extensions, strictly regulating access to the host system.
- **JSON-First Structures:** Page layouts are stored as machine-readable JSON (Bootstrap 5 compatible), allowing AI agents to understand and autonomously modify the UI.
- **Multi-Provider AI:** Built-in support for OpenAI, Anthropic (Claude), and local models via Ollama.

---

## 🛠 Architecture & Philosophy

Curly aims to bring the flexibility of modern agentic workflows to the accessibility of PHP. It ensures that data sovereignty and infrastructure remain manageable for developers who prefer a simplified entry point compared to `npm`-heavy stacks.

### Machine-Readable Layouts
Instead of raw, unstructured HTML, Curly uses JSON trees. This allows an AI agent to see the "skeleton" of a page rather than just a string of text:
{
    "type": "container",
    "children": [
        {
            "type": "row",
            "children": [
                {
                    "type": "column",
                    "cols": 12,
                    "children": [{"type": "heading", "content": "Agent-Managed Content"}]
                }
            ]
        }
    ]
}

---

## 📦 Installation

1. **Clone the Repository:**
   git clone https://github.com/RHEINMAINTECH/Curly.git
2. **Environment Setup:**
   Copy `.env.example` to `.env` and provide your API keys for the chosen AI providers.
3. **Run Installer:**
   php install/install.php
4. **Configure Web Server:**
   Point your web server's document root to the `public` directory.

---

## 📄 License & Contact

This project is open-source software licensed under the [MIT License](LICENSE).

**Developed by:** RheinMainTech GmbH  
**Website:** [rheinmaintech.com](https://rheinmaintech.com)  
**Email:** support@rheinmaintech.com

Built for the Agentic Web. An open alternative for maximum accessibility.