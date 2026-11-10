# 🕉️ OnlinePuja.live — Master Ecosystem Architecture, Environment Audit & Operational Manual

> **Document Classification:** Universal System Specification & Architecture Audit  
> **Target Audience:** Engineering Leads, AI Agents, DevOps & Growth Executives  
> **Last Updated:** November 2026  
> **Canonical Domain:** `https://onlinepuja.live`  

---

## 1. Executive Vision & Ecosystem Summary

**OnlinePuja.live** is a full-stack, autonomous spiritual technology ecosystem designed to bridge millions of devotees across Bharat and the global Hindu diaspora with sacred Vedic temples, verified pandits, authentic Jyotish consultations, and personalized puja rituals.

Unlike traditional static booking portals, OnlinePuja operates on an **Autonomous 4-Tier AI Nervous System ("OnlinePuja Brain")**:
1. **Interactive Conversational Voice AI ("Vedic Pandit Ji")**: Real-time spoken Hindi and Sanskrit consultation embedded into the web platform, manager apps, and WhatsApp.
2. **Self-Healing Telemetry**: Hourly scans of the database detecting abandoned Sankalps, broken funnels, or webhook dropouts, autonomously triggering recovery sequences.
3. **Multi-Agent Growth Swarm (Paperclip)**: 8 autonomous agents coordinating B2B outreach, festival campaigns, social media generation, and SEO/AEO content indexing.
4. **Devotee Lifecycle Automation**: Autonomous daily broadcasts of Vedic Hindi Panchang and 12-Rashi Dainik Rashifal at 06:30 AM IST.

---

## 2. Infrastructure Topography & Hosting Environment

### 2.1 Hardware & Cloud Specifications
| Resource | Parameter | Details |
| :--- | :--- | :--- |
| **Primary Host VPS** | Cloud Provider | Hostinger Cloud VPS (`srv1351797.hstgr.cloud`) |
| **Public IPv4** | Address | `200.234.41.231` |
| **Operating System** | Kernel / Distro | Ubuntu 24.04 LTS (x86_64 Linux 6.8.0) |
| **Server Control Panel**| Software | CloudPanel 2.x (Nginx, PHP-FPM, MySQL 8.0, Node.js) |
| **SSH Access** | User & Sanction | `root@200.234.41.231` (Key-based authentication) |
| **Edge CDN & DNS** | Network | Cloudflare (Edge SSL, Proxy, DDoS Mitigation) |

---

### 2.2 Domain & Reverse Proxy Routing Matrix

All web traffic enters port 80/443 on Nginx (`/etc/nginx/sites-enabled/`) and routes to designated backend services or Docker containers:

```
                                  Cloudflare Edge (HTTPS / TLS 1.3)
                                                 │
                                                 ▼
                             Host Nginx Reverse Proxy (200.234.41.231)
                                                 │
         ┌───────────────────┬───────────────────┼───────────────────┬───────────────────┐
         ▼                   ▼                   ▼                   ▼                   ▼
 https://onlinepuja.live    wss://rtc...         https://apps...     https://agents...   https://wa...
 (Laravel 10 Backend +       (LiveKit WebRTC     (Flutter Web Apps   (Paperclip Swarm:   (Evolution API:
 Blade Customer Web)         Media Engine: 7880) Manager & Customers) Port 3300)          Port 18080)
```

| Domain / Subdomain | Target Upstream | Description |
| :--- | :--- | :--- |
| `https://onlinepuja.live` | PHP-FPM 8.2 (CloudPanel) | Main Devotional Website, Puja Catalog, Sankalp Flow & API |
| `wss://rtc.onlinepuja.live`| `http://127.0.0.1:7880` | LiveKit WebRTC Media Server for Real-Time Voice & Video |
| `https://apps.vmstudio.digital` | `/home/vmstudio-apps/htdocs/` | Static/PWA Hosting for Manager, Astrologer & Customer Flutter Apps |
| `https://agents.vmstudio.digital`| `http://127.0.0.1:3300` | Paperclip Autonomous Multi-Agent Swarm (8 Active Specialists) |
| `https://ai.vmstudio.digital` | `http://127.0.0.1:20128` | OmniRoute & FreeLLMAPI Intelligent Gateway |
| `https://wa.vmstudio.digital` | `http://127.0.0.1:18080` | Evolution API v2 (WhatsApp Baileys Multi-Device Gateway) |

---

### 2.3 Docker Containers Running on Host

```bash
docker ps --format 'table {{.Names}}\t{{.Image}}\t{{.Status}}\t{{.Ports}}'
```

1. **`evolution_api`** (`evoapicloud/evolution-api:latest`): WhatsApp REST/Socket Gateway on port `18080`.
2. **`evolution_postgres`** (`postgres:16-alpine`): Storage for Evolution WhatsApp sessions and message logs.
3. **`evolution_redis`** (`redis:7-alpine`): In-memory cache for WhatsApp session keys.
4. **`livekit-livekit-1`** (`livekit/livekit-server:latest`): Low-latency WebRTC SFU engine (host networking, port `7880`).
5. **`livekit-coturn-1`**: TURN/STUN server for WebRTC NAT traversal (port `3478`).
6. **`paperclip_app`** (`ghcr.io/paperclipai/paperclip:latest`): Autonomous AI Agent Swarm orchestrator (port `3300`).
7. **`paperclip_postgres`** (`postgres:17-alpine`): Dedicated PostgreSQL database for Paperclip company `fcb854e2-4cff-4d8f-a0e3-587e5f1cfc8d`.
8. **`omniroute`** (`ghcr.io/diegosouzapw/omniroute:latest`): AI Inference Router on port `20128`.
9. **`freellmapi`** (`ghcr.io/tashfeenahmed/freellmapi:latest`): Keyless fallback LLM provider.

---

## 3. Databases & Data Stores

### 3.1 Primary MySQL Database (`onlinepujaastro`)
- **Connection**: MySQL 8.0 on `127.0.0.1:3306`
- **Database Name**: `onlinepujaastro`
- **Username**: `opastro`
- **Critical Tables**:
  - `pujas`: Catalog of 34 authentic temple rituals (e.g., Kashi Vishwanath Rudrabhishek, Mahakal Ayushya Havan, Kolhapur Lakshmi Kubera).
  - `puja_orders`: Orders ledger with `sankalp_gotra`, `devotee_names`, `order_total_price`, and `puja_order_status` (`pending`, `placed`, `ongoing`, `completed`).
  - `users`: Devotee accounts with contact numbers, Gotra, and astrological profiles.
  - `astrologers`: Verified Vedic pandits and astrologers with call/chat status and rates.
  - `b2b_leads`: Seeded directory of 10+ corporate gifting, travel, and temple trusts.
  - `ai_agents`: Metadata for 6 autonomous agents (`panchang`, `rashifal`, `seo_blog`, `festival`, `b2b`, `retention`).
  - `whatsapp_optouts`: Opt-out registry respecting devotee privacy (`STOP`/`START`).
  - `whatsapp_broadcast_logs`: Anti-spam audit ledger enforcing dispatch jitter.
  - `social_accounts`: Encrypted tokens for Meta Facebook Page (`1252263081299388`) and Instagram (`17841434469409429`).
  - `social_posts`: Staged social post queue for multi-channel publishing.

---

## 4. Full-Stack Software Suite

```
                               ┌────────────────────────────────────────────────┐
                               │           OnlinePuja Software Suite            │
                               └───────┬─────────────────┬──────────────────┬───┘
                                       │                 │                  │
         ┌─────────────────────────────┴─────┐   ┌───────┴──────────┐   ┌───┴────────────────────────┐
         ▼                                   ▼   ▼                  ▼   ▼                            ▼
 🌐 Web Platform                      📱 Customer App        🔮 Astrologer App       💼 Manager Ops App
 • Laravel 10 + Blade                 • Flutter (Android)    • Flutter (Android)     • Flutter (Web & Android)
 • /resources/views/frontend/         • Sankalp Booking      • Live Call & Chat      • Executive Telemetry
 • Live Voice Mic Widget              • Kundli Generator     • Dakshina Payouts      • Voice Copilot Mic
 • /marketing Cockpit                 • Video Darshan        • Ritual Queue          • Live Sankalp Fulfillment
```

### 4.1 Backend Engine (`/home/onlinepujalive/htdocs/onlinepuja.live`)
- **Framework**: Laravel 10.x running under PHP 8.2 FPM.
- **Key Architectural Services** (`app/Services/`):
  - `OnlinePujaBrainService.php`: Core system awareness, telemetry health scoring, self-fixing cart recovery, and Vedic Voice consultation.
  - `AiEngineService.php`: Fallback chain (OmniRoute → Gemini Flash → OpenRouter → Pollinations).
  - `EvolutionWaService.php`: Baileys WhatsApp integration.
  - `SearchConsoleService.php`: Google Search Console indexing analytics via service account.
  - `MetaCapiService.php`: Server-side Conversions API event dispatch.
  - `FestivalCampaignEngine.php`: Hindu festival date calculations and promo schedules.
  - `VedicAstrologyEngine.php`: Authentic Kundli, Gochar, and Dosha matching algorithms.
- **Key Artisan Commands**:
  - `php artisan brain:autopilot {--self-fix}`: Hourly self-awareness and abandoned cart healing.
  - `php artisan whatsapp:daily-panchang-broadcast {--dispatch}`: Daily 06:30 AM IST Panchang broadcast.
  - `php artisan social:publish`: Dispatches queued posts to Meta/Instagram/YouTube.

### 4.2 The Triple Flutter Applications (`apps/`)
Located in workspace `d:\Dev\onlinepuja-live\apps\`:
1. **`onlinepuja-customer`**: Native devotee mobile app for booking pujas, viewing live video darshan, receiving prasad tracking, and free Kundli creation.
2. **`onlinepuja-astrologer`**: Partner app for verified acharyas to accept real-time voice calls, text consultations, and fulfill Gotra Sankalps.
3. **`onlinepuja-manager`**: Executive operations hub deployed as a Web PWA at `https://apps.vmstudio.digital/onlinepuja-manager/` and native Android APK. Features real-time GMV radar, B2B dispatch, WhatsApp inbox, and the **Voice AI Operations Copilot**.

---

## 5. Integrations & API Credentials Matrix

| Integration | Identifier / Key | Endpoint / Scope | Current Operational Status |
| :--- | :--- | :--- | :--- |
| **LiveKit WebRTC** | `ke0hzp3b19g4j28` | `wss://rtc.onlinepuja.live` | **100% OPERATIONAL** |
| **Meta Facebook Page** | Page ID: `1252263081299388` | `pages_manage_posts`, `pages_read_engagement` | **100% OPERATIONAL** (Never-Expiring Page Token) |
| **Meta Conversions (CAPI)**| Pixel: `1738490804042986` | `https://graph.facebook.com/v21.0` | **100% OPERATIONAL** |
| **Google Search Console**| `firebase-adminsdk-fbsvc@...` | `https://onlinepuja.live/` | **100% OPERATIONAL** (31 clicks / 3,376 impressions verified) |
| **Brevo SMTP** | User: `94cff3001@smtp-brevo.com` | `smtp-relay.brevo.com:587` | **100% OPERATIONAL** |
| **WhatsApp Evolution API**| Instance: `onlinepuja` | `http://127.0.0.1:18080` (Port 18080) | **CONFIGURED & STANDBY** (Awaiting device linking for `7007158014`) |
| **Instagram Graph API** | IG ID: `17841434469409429` | `instagram_basic`, `instagram_content_publish` | **PENDING ASSET LINKING** (Under Meta Developers Use Case) |
| **OmniRoute AI Gateway** | Port: `20128` | `http://127.0.0.1:20128/v1` | **100% OPERATIONAL** |
| **Paperclip Agents** | Company: `fcb854e2-...` | `http://127.0.0.1:3300/api` | **100% OPERATIONAL** (8 Active Agents) |

---

## 6. Real-Time Telemetry & Health Benchmarks

As reported by `GET /api/brain/pulse`:
- **Overall Brain Health Score**: `85%` (Healthy)
- **Active Catalog Pujas**: `34`
- **Total Registered Devotees**: `19`
- **All-Time Orders**: `1`
- **Pending Sankalps**: `0`
- **Online Astrologers**: `10`
- **Active Autonomous Agents**: `6` internal Laravel + `8` Paperclip

---

## 7. Master Operational & Next-Step Checklist

### 7.1 Immediate Manual Actions (2 Human Steps)
- [ ] **WhatsApp Final Device Linking**: Open WhatsApp on phone `917007158014` → *Linked Devices* → Enter fresh pairing code or scan QR. Once paired, daily 06:30 AM broadcasts and automated cart recovery messages will fire automatically.
- [ ] **Meta Instagram Asset Link**: In [Meta Developers Use Cases](https://developers.facebook.com/apps/1738490804042986/use_cases/?business_id=538666562672391), click **Customize** next to *Manage messaging & content on Instagram* and add `@onlinepuja.live` to the test assets.

### 7.2 Autonomous Continuous Operations
- [x] **Hourly Self-Fixing & Cart Recovery**: Active via `brain:autopilot --self-fix` in Laravel Cron.
- [x] **2-Way WhatsApp Consultations**: Active via `WhatsAppWebhookController.php` with direct fallback to `OnlinePujaBrainService`.
- [x] **Web Devotee Voice Assistant**: Active on [https://onlinepuja.live/](https://onlinepuja.live/) with floating mic widget.
- [x] **Manager App Voice Copilot**: Active on [https://apps.vmstudio.digital/onlinepuja-manager/](https://apps.vmstudio.digital/onlinepuja-manager/) and release APK.
- [x] **Search Console Auto-Tracking**: Verified indexing for 30-day click and impression monitoring.
