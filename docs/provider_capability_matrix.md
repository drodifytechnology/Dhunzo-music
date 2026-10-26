# Provider Capability Matrix

This matrix documents the features, latency profiles, languages, and failover roles for all music and lyrics providers supported by the platform.

---

## 1. Music Streaming & Metadata Providers

| Provider | Type | Latency Profile | Audio Quality Options | Artwork Quality | Role |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **JioSaavn** | Primary API | Fast (150 - 350ms) | 96kbps, 160kbps, 320kbps MP4/AAC | 50x50, 150x150, 500x500 | Default Primary Provider |
| **YouTube** | Secondary API | Moderate (300 - 600ms) | Adaptive Opus / M4A / WebM | Default, Medium, High, Maxres | Fallback & Video Search |
| **JioSaavn Direct** | Client Fallback | Direct (200 - 450ms) | 96kbps, 160kbps, 320kbps | 500x500 | Client Remote GitHub Fallback (Priority 1) |
| **YouTube Piped** | Client Fallback | Variable (400 - 900ms) | Adaptive Audio Stream | High, Maxres | Client Remote GitHub Fallback (Priority 2) |

---

## 2. Lyrics Providers & AI Fallback Cascade

| Provider | Type | Primary Languages | Sync Format | DB Persisted | Priority |
| :--- | :--- | :--- | :--- | :---: | :---: |
| **LRCLIB** | REST Catalog | Global, English, Pop | Line-synced (`[mm:ss.xx]`) | No (In-Memory Cache) | 1 |
| **KuGou** | REST Catalog | Asian, Regional, International | Line-synced | No (In-Memory Cache) | 2 |
| **NetEase** | REST Catalog | Asian, Global, Regional | Line-synced | No (In-Memory Cache) | 3 |
| **Musixmatch** | REST Catalog | Multilingual Worldwide | Line-synced (Subtitles) | No (In-Memory Cache) | 4 |
| **Genius** | REST Catalog | Global, Rap, Indipop | Plain text lyrics | No (In-Memory Cache) | 5 |
| **YouTube Captions** | VTT/SRT Stream | Multilingual Auto-subs | Time-coded | No (In-Memory Cache) | 6 |
| **AI Speech-to-Text** | AI Audio Model | **Bhojpuri, Hindi, Bengali, Punjabi, English** | Line-synced (`[mm:ss.xx]`) | **Yes (`generated_lyrics`)** | **7 (Final Fallback)** |

---

## 3. Circuit Breaker Parameters

- **Threshold**: 5 consecutive connection or timeout failures trip the breaker.
- **Cooldown**: 60 seconds reset window before half-open state testing.
- **Negative Caching**: Songs with 404 responses across all providers are cached for 1800s to avoid redundant external network load.
