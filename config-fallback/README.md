# Remote GitHub Fallback Configuration Engine

This directory contains the remote JSON configuration (`dhunzo_fallback_config.json`) consumed by Flutter client applications (such as Dhunzo / BlackHole player) when the primary Laravel Gateway experiences network timeouts, HTTP 5xx errors, or maintenance outages.

---

## 🎯 Purpose & Design Architecture

1. **Zero-App-Update Resilience**:
   If third-party endpoints (JioSaavn / YouTube / Piped) change their schemas, query parameters, or token formats, the Flutter client can be updated remotely by committing changes to this repository without requiring an App Store or Play Store app release.
2. **GitHub Raw CDN Hosting**:
   The Flutter app fetches this configuration over HTTPS from GitHub Raw:
   ```http
   GET https://raw.githubusercontent.com/drodifytechnology/Dhunzo-music/main/config-fallback/dhunzo_fallback_config.json
   ```
3. **Local & CDN Caching**:
   The Flutter client caches this JSON locally for **3600 seconds (1 hour)** to minimize bandwidth and latency while ensuring fallback availability even when offline.

---

## 📋 Schema Overview (`dhunzo_fallback_config.json`)

```json
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "version": "1.0.0",
  "updated_at": "2026-10-25T21:40:00Z",
  "primary_api": {
    "name": "Dhunzo Domain API",
    "base_url": "http://10.0.2.2:8000",
    "timeout_ms": 3500,
    "enabled": true
  },
  "failover_policy": {
    "enabled": true,
    "trigger_on_timeout": true,
    "trigger_on_http_status": [404, 500, 502, 503, 504],
    "provider_priority": ["jiosaavn", "youtube"],
    "cache_ttl_seconds": 3600
  },
  "providers": { ... },
  "telemetry": {
    "enabled": true,
    "endpoint": "http://10.0.2.2:8000/api/v1/telemetry/fallback-event"
  }
}
```

---

## 🔧 How to Update Fallback Endpoints

1. Edit `dhunzo_fallback_config.json`.
2. Update `"version"` (e.g., from `1.0.0` to `1.0.1`) and `"updated_at"`.
3. Commit and push to `main` branch.
4. Changes take effect on client devices upon next cache expiration or app refresh.
