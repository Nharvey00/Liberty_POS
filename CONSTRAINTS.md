# CONSTRAINTS.md — LibertyPOS Operational & Project Boundaries

> **CRITICAL INSTRUCTION FOR AI AGENTS:**
> This document defines the real-world operational, academic, technical, and communication constraints governing the **LibertyPOS** project. Any proposed plan, code change, troubleshooting guide, or documentation update **must** fit within these boundaries. Do **not** assume extra team capacity, unverified paid tooling, or complex multi-step workflows that violate the working-style preferences below.

---

## 1. Timeline & Deadlines

* **Academic Course Context:**
  * The project is actively being evaluated for **IT 14/L (Professional Track 5: Service Management 1 — Software Engineering)**, starting with the **Week 1 Activity: IT 12/L Mini Project Retrospective**.
* **Presentation Deliverable Date:**
  * The Week 1 Deliverable specification lists the oral presentation date as **October 19** (evaluating Project Overview, Requirements Analysis, Testing Results, Retrospective, and Improvement Recommendations for 100 total points).
* **Live Infrastructure Lifecycle Dates:**
  * **DigitalOcean Server (`tionkolpg-server`):** Provisioned on **October 6, 2026**.
  * **Domain Registration (`tionkolpg.com`):** Active from **October 5, 2026 to October 6, 2027** (with Namecheap Auto-Renew enabled).
  * **Namecheap Private Email Trial (`Scale Email`):** Set to expire on **November 6, 2026** ($71.88 renewal price); this trial is unused by the system and ignored.
* **Immediate Operational Priority:**
  * Maintaining uninterrupted live uptime on `https://tionkolpg.com` (including the in-place PHP 8.2 security patch for `CVE-2026-109618` and `CVE-2026-109619`) while completing the missing slides (`Section 03: Testing Results`, `Section 04: Project Retrospective`, and `Section 05: Lessons Learned & Prioritized Improvements`) for the IT 14/L presentation deck.

---

## 2. Team Setup

* **Development Team (2 Student Developers):**
  * **Nunilon Harvey H. Morong** (GitHub repository owner: `Nharvey00/Liberty_POS`, primary operator of the local Windows machine `Harvey Morong` during server/DNS administration).
  * **Gerald Wynn R. Belena** (Co-developer listed on the IT 14/L project retrospective presentation).
  * **Internal Division of Tasks Between the Two Developers:** Not yet established.
* **Client & Business Stakeholders (Liberty LPG Center — Davao City):**
  * **Owner:** Jose Maria S. Tionko (holds the primary company Google/Gmail account and phone used for 2FA/QR verification).
  * **Operations Manager:** 1 manager overseeing store operations.
  * **Store / Operational Teams:** 3 teams with 3 employees each:
    * **Sales / Store Staff (3 employees):** Daily POS, customer, and stock-in/stock-out encoders.
    * **Accounting Staff (3 employees):** Consumers of the exported Excel sales reports.
    * **Delivery Staff (3 employees):** Fulfillment staff for delivery orders.

---

## 3. Technical & Resource Limitations

* **Academic Project Scope Constraint:**
  * Under IT 14/L rules, the team is **strictly discouraged from starting a new project from scratch**. All engineering, testing, and retrospective work must build upon the existing IT 12/L `LibertyPOS` system and its actual development history.
* **Owner Account & 2FA Access Bottleneck:**
  * Administrative accounts (`tionkolpg` on Namecheap, Laravel Forge, and the company Google account) are tied to the business owner's email. Logging into the owner's Gmail account requires scanning a QR code / 2FA prompt via the owner's device unless automated email forwarding or backup access is maintained.
* **Single-Server & Single-Branch Production Footprint:**
  * The system runs on a single DigitalOcean Droplet (`159.89.204.80`, Singapore region, Ubuntu 24.04, PHP 8.2) managed by Laravel Forge and connected to a cloud Supabase PostgreSQL database.
  * The current application architecture supports **only a single branch** (Catalunan main branch), even though the business physically operates three branches (`Bangkal`, `Catalunan`, and `Victoria Plaza`).
* **Local Developer Environment Constraints:**
  * **OS & Browser:** Windows 10/11 (`Version 10.0.26200.9711`) and Google Chrome.
  * **IDE:** Visual Studio Code.
  * **Terminal Permissions:** Standard Command Prompt sessions run without elevation by default (`C:\Users\moron>`); network administration commands such as `ipconfig /flushdns` require explicitly launching Command Prompt via **Run as administrator**.
* **Local Network / ISP DNS Caching Delays:**
  * Local Wi-Fi networks in Davao City exhibit DNS caching delays after domain registrar changes compared to mobile data (4G/5G) connections.

---

## 4. Working-Style Preferences

* **Strict Step-by-Step Execution During Troubleshooting:**
  * When resolving infrastructure, server, or configuration issues, the user prefers **clear, numbered, one-step-at-a-time guidance** (completing Step 1 before moving to Step 2) with exact UI click paths rather than long theoretical explanations or multiple competing workflows at once.
* **Visual & Screenshot-Driven Verification:**
  * The user frequently shares screenshots of live dashboards (Laravel Forge, Namecheap, Chrome errors, Command Prompt) to verify exact states before clicking destructive or irreversible buttons. Agents should inspect these screenshots carefully and point to exact labels on the screen.
* **Concise, Non-Specific Slide Content Matching Existing Deck Style:**
  * For presentation deliverables, the user prefers generating **only the missing slides** rather than regenerating existing work, and keeping slide copy **high-level, plain-language, concise, and non-specific** so it matches the clean, minimalist layout of the existing PowerPoint deck.
* **Multi-AI Workflow (Gemini + Claude):**
  * The user uses Gemini for infrastructure troubleshooting, project architecture documentation, and prompt engineering, and uses **Claude** to generate slide content/layouts from targeted prompts.
* **Precision Ground-Truth Documentation:**
  * For project specification files (`PROJECT.md`, `ARCHITECTURE.md`, `DECISIONS.md`, `CONSTRAINTS.md`), the user requires literal, factual bullet points, zero fabricated defaults, and explicit flagging of any unconfirmed details.