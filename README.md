# Dhunzo-music / BlackHole Music & Lyrics Platform

Dhunzo — A Flutter-based music streaming platform for discovering and listening to music, with a modern UI, playlists, and audio playback, powered by an enterprise-grade, high-availability **Laravel 10 Central Gateway**, a **Remote GitHub Fallback Engine**, **API Testing Collections**, and comprehensive **Integration Documentation**.

---

## 📂 Repository Modular Architecture

```
/ (Repository Root)
├── backend/                  # Full Laravel 10 Central Music & Lyrics Gateway
│   ├── app/
│   │   ├── Http/Controllers/Api/V1/ (Music, Lyrics, Telemetry Controllers)
│   │   ├── Services/Music/          (SaavnProvider, YouTubeProvider, Resolver)
│   │   ├── Services/Lyrics/         (LrcLib, KuGou, NetEase, Scorer, Breaker)
│   │   ├── DTOs/                    (Standardized Song, Album, Lyrics DTOs)
│   │   └── Support/                 (Unified ApiResponse Envelope & UUID Middleware)
│   ├── config/                      (dhunzo.php, cache, provider settings)
│   └── tests/                       (Unit & Feature parser and multilingual benchmarks)
│
├── config-fallback/          # Remote GitHub-hosted Fallback Engine
│   ├── dhunzo_fallback_config.json  # Live remote configuration for Flutter client
│   └── README.md                    # Fallback schema update instructions
│
├── postman/                  # Ready-to-import Postman Collections & Environments
│   ├── Dhunzo_Music_API.postman_collection.json
│   ├── Dhunzo_Lyrics_API.postman_collection.json
│   └── Dhunzo_Staging.postman_environment.json
│
└── docs/                     # Specifications & Integration Guides for Flutter Team
    ├── architecture_overview.md
    ├── flutter_integration_guide.md
    └── provider_capability_matrix.md
```

---

## 🚀 Quick Start Guide

### 1. Central Gateway (`backend/`)

Navigate to the backend directory and set up environment:
```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --force
```

Start local development server:
```bash
php artisan serve --port=8000
```

Run test suite (42 tests, 248 assertions, 100% pass):
```bash
php artisan test
```

Production Docker Deployment:
```bash
docker compose up -d --build
```

---

### 2. Remote GitHub Fallback (`config-fallback/`)

Contains `dhunzo_fallback_config.json` fetched directly by Flutter client applications from GitHub Raw CDN.
- Configures failover conditions (timeout > 3.5s or HTTP 5xx).
- Manages priority routing (`JioSaavn Direct` -> `YouTube Piped`).
- See [config-fallback/README.md](file:///Users/macbookprom12020lsbd/Downloads/music_api/config-fallback/README.md) for update protocols.

---

### 3. Postman Collections (`postman/`)

Import directly into Postman to test all endpoints:
- **[Dhunzo_Music_API.postman_collection.json](file:///Users/macbookprom12020lsbd/Downloads/music_api/postman/Dhunzo_Music_API.postman_collection.json)**: Global search, song details, trending, recommendations, and telemetry.
- **[Dhunzo_Lyrics_API.postman_collection.json](file:///Users/macbookprom12020lsbd/Downloads/music_api/postman/Dhunzo_Lyrics_API.postman_collection.json)**: Multi-provider synced lyrics and AI Speech-to-Text fallback.
- **[Dhunzo_Staging.postman_environment.json](file:///Users/macbookprom12020lsbd/Downloads/music_api/postman/Dhunzo_Staging.postman_environment.json)**: Pre-configured variables for local, staging, and Android emulator endpoints.

---

### 4. Technical Documentation (`docs/`)

- **[Architecture Overview](file:///Users/macbookprom12020lsbd/Downloads/music_api/docs/architecture_overview.md)**: End-to-end failover topology and AI fallback lifecycle.
- **[Flutter Integration Guide](file:///Users/macbookprom12020lsbd/Downloads/music_api/docs/flutter_integration_guide.md)**: Dart code samples for remote config hydration, automatic failover, and synced LRC playback.
- **[Provider Capability Matrix](file:///Users/macbookprom12020lsbd/Downloads/music_api/docs/provider_capability_matrix.md)**: Feature matrix, bitrates, latency profiles, and circuit breaker settings.

---

## 📄 License
MIT License.
