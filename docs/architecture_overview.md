# Architecture Overview

## 1. System High-Level Topology

```
                  ┌─────────────────────────────────────┐
                  │      Flutter Client (BlackHole)     │
                  └───────────────┬─────────────────────┘
                                  │
                 Primary Request  │  3.5s Timeout / Network Failure
                 (HTTP 200 OK)    │  Failover Trigger
                                  ▼
        ┌────────────────────────────────────────────────────────┐
        │                                                        │
        ▼                                                        ▼
┌──────────────────────────────┐              ┌─────────────────────────────────────┐
│  Laravel 10 Central Gateway  │              │  Remote GitHub Fallback Engine      │
│  (backend/)                  │              │  (config-fallback/dhunzo_fallback)  │
└──────────────┬───────────────┘              └──────────────────┬──────────────────┘
               │                                                 │
      ┌────────┴────────┐                               ┌────────┴────────┐
      ▼                 ▼                               ▼                 ▼
┌───────────┐    ┌─────────────┐                 ┌─────────────┐    ┌───────────┐
│ JioSaavn  │    │  Multi-Src  │                 │ JioSaavn    │    │ YouTube   │
│ & YouTube │    │   Lyrics    │                 │ Direct CDN  │    │ Piped CDN │
│ Resolvers │    │   Cascade   │                 └─────────────┘    └───────────┘
└───────────┘    └──────┬──────┘
                        │
                  Lyrics Not Found
                        ▼
               ┌───────────────────┐
               │  AI Speech-to-    │
               │  Text Fallback    │
               │  (Whisper/Align)  │
               └────────┬──────────┘
                        │
                        ▼
               ┌───────────────────┐
               │  Database Cache   │
               │ (generated_lyrics)│
               └───────────────────┘
```

---

## 2. Core Subsystems

### A. Central Music & Lyrics Gateway (`backend/`)
- **Framework**: Laravel 10 (PHP 8.2+ / PHP 8.5)
- **Primary Music Resolver**: JioSaavn REST parser with fallback to YouTube Search API.
- **Lyrics Engine**: 7-tier provider cascade (LRCLIB, KuGou, NetEase, Musixmatch, Genius, YouTube Captions).
- **AI Speech-to-Text Fallback**: Runs when lyrics are uncatalogued. Fetches audio stream, analyzes language (Bhojpuri, Hindi, Bengali, Punjabi), transcribes vocal track with millisecond-accurate timestamps (`[mm:ss.xx]`), and persists the result to SQLite/MySQL/PostgreSQL.
- **Reliability**: Circuit breakers (5 consecutive failures trip for 60s cooldown), negative cache (1800s), rate limiting (60 req/min).

### B. Remote GitHub Fallback Engine (`config-fallback/`)
- Hosted on GitHub Raw CDN.
- Configures dynamic failover rules, provider priority orders, timeout thresholds, and direct API endpoints.
- Allows zero-app-release updates when third-party streaming schemas change.

### C. Client Failover Mechanism
- App sends requests with a strict client-side timeout (e.g., 3500ms).
- If Laravel API succeeds, playback begins immediately.
- If Laravel API times out, returns HTTP 5xx, or network is down, the client seamlessly invokes GitHub fallback routes.
- The client silently transmits a telemetry ping to `/api/v1/telemetry/fallback-event` for monitoring.
