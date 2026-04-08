<p align="center">
  <img src="docs/assets/img/logo.svg" width="200" height="200" alt="Curly CMS Logo">
</p>

# Curly: The AI-Native Agentic CMS

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.0-8892bf.svg)](https://php.net)
[![Stability](https://img.shields.io/badge/stability-pre--alpha-red.svg)](https://github.com/RHEINMAINTECH/Curly)

**Curly** ist ein AI-natives Content Management System, das speziell für die Zusammenarbeit mit autonomen Agenten entwickelt wurde. 

Es ist die **niedrigschwellige PHP-Alternative zu Cloudflares EmDash**. Während EmDash eine komplexe `npm`-basierte Infrastruktur benötigt, setzt Curly auf ein klassisches PHP-Backend. Dies ermöglicht Entwicklern und Agenturen einen extrem einfachen Zugang und ein unkompliziertes Deployment auf Standard-Webservern, ohne auf moderne "Agentic Workflows" verzichten zu müssen.

> [!IMPORTANT]
> **Projekt-Status: Pre-Alpha**. Das Projekt befindet sich in einem sehr frühen Stadium. APIs, Protokoll-Implementierungen und Datenbank-Schemata können sich jederzeit ändern.

---

## 🚀 Key Features

- **PHP-Native (Low-Threshold):** Schnelles Deployment auf jedem Standard-Server. Kein komplexer Node.js/npm-Build-Stack für den Kernbetrieb erforderlich.
- **Agentic-Ready (A2A):** Native Unterstützung für das **Agent-to-Agent** Protokoll zur Kommunikation zwischen verschiedenen KI-Instanzen.
- **Modell-Kontext (MCS):** Implementierung des **Model Context Server** Protokolls, um LLMs strukturierten Zugriff auf Website-Daten und Layouts zu geben.
- **Secure Extension Sandbox:** Ein sicheres Ausführungsumfeld für Erweiterungen, das den Zugriff auf das Host-System strikt reglementiert.
- **JSON-Strukturen:** Seitenlayouts werden als maschinenlesbares JSON gespeichert (Bootstrap 5 kompatibel), sodass KI-Agenten das Design verstehen und autonom modifizieren können.
- **Multi-Provider AI:** Support für OpenAI, Anthropic (Claude) und lokale Modelle (Ollama).

---

## 🛠 Architektur & Philosophie

Curly verfolgt das Ziel, die Flexibilität von EmDash in die PHP-Welt zu bringen. Damit bleibt die Hoheit über Daten und Erweiterungen einfach handhabbar. 

### JSON-First Layouts
Anstatt unstrukturiertem HTML nutzen wir JSON-Bäume. Ein Agent sieht nicht nur Text, sondern eine Struktur:
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

## 📦 Installation (Kurzform)

1. **Repository klonen:**
   git clone https://github.com/RHEINMAINTECH/Curly.git
2. **Setup:**
   `.env.example` zu `.env` kopieren und API-Keys für die gewünschten KI-Provider hinterlegen.
3. **Install-Script:**
   php install/install.php
4. **Webserver:**
   Document-Root auf den Ordner `public` zeigen lassen.

---

## 📄 Lizenz & Kontakt

Das Projekt steht unter der [MIT Lizenz](LICENSE).

**Entwickelt von:** RheinMainTech GmbH  
**Web:** [rheinmeintech.com](https://rheinmeintech.com)  
**E-Mail:** support@rheinmeintech.com

Gebaut für das Agentic Web. Als offener Gegenentwurf für maximale Zugänglichkeit.