# Flutter Client Integration Guide

This guide describes how the Flutter client app integrates with the **Primary Laravel Gateway** and the **Remote GitHub Fallback Layer**.

---

## 1. Remote Configuration Hydration

At app startup (or once every 60 minutes), fetch and cache `dhunzo_fallback_config.json`:

```dart
Future<Map<String, dynamic>> fetchRemoteFallbackConfig() async {
  const configUrl = 'https://raw.githubusercontent.com/drodifytechnology/Dhunzo-music/main/config-fallback/dhunzo_fallback_config.json';
  
  try {
    final response = await http.get(Uri.parse(configUrl)).timeout(const Duration(seconds: 4));
    if (response.statusCode == 200) {
      final config = jsonDecode(response.body);
      await saveConfigToLocalCache(config);
      return config;
    }
  } catch (e) {
    // If offline, read cached config
    return await loadCachedConfig();
  }
  return defaultHardcodedConfig;
}
```

---

## 2. Song Search with Automatic Failover

```dart
Future<List<SongItem>> searchSongs(String query) async {
  const primaryUrl = 'http://api.dhunzo.com/api/v1/search';
  
  try {
    // 1. Query Laravel Gateway with 3.5s Timeout
    final response = await http.get(
      Uri.parse('$primaryUrl?q=${Uri.encodeComponent(query)}&limit=15')
    ).timeout(const Duration(milliseconds: 3500));

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      if (data['success'] == true && (data['data'] as List).isNotEmpty) {
        return (data['data'] as List).map((i) => SongItem.fromJson(i)).toList();
      }
    }
  } catch (e) {
    // Timeout or 5xx Network Error -> Trigger Failover
  }

  // 2. Seamless GitHub Fallback Execution
  return await executeFallbackSearch(query);
}
```

---

## 3. Synced LRC Lyrics Consumption

Search for synchronized lyrics using the lyrics search endpoint:

```http
GET /api/v1/lyrics/search?title={title}&artist={artist}&duration={seconds}
```

The response provides both an array of millisecond timestamps and raw LRC text:
- `data.type`: `"line_synced"`
- `data.lines`: `[ { "start_ms": 12000, "end_ms": 32800, "text": "..." } ]`
- `data.raw`: `"[00:12.00] ...\n[00:33.30] ..."`
- `data.provider`: Either an existing catalog provider (`"lrclib"`, `"kugou"`, etc.) or `"ai_speech_to_text"` if extracted automatically.

When seeking or playing back, compare current player position in milliseconds against `start_ms` and `end_ms` to highlight the active lyric line in real time.
