<!DOCTYPE html>
<html lang="bn" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dhunzo AI Lyrics Studio — Real-Time Synced Player</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;600;700&family=Noto+Sans+Devanagari:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #07090e;
            --bg-surface: rgba(18, 24, 38, 0.7);
            --bg-surface-elevated: rgba(26, 35, 54, 0.85);
            --border-glass: rgba(255, 255, 255, 0.08);
            --border-glow: rgba(16, 185, 129, 0.4);
            --primary: #10b981;
            --primary-glow: rgba(16, 185, 129, 0.35);
            --secondary: #6366f1;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --active-lyric: #ffffff;
            --active-lyric-glow: rgba(16, 185, 129, 0.8);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', 'Noto Sans Bengali', 'Noto Sans Devanagari', -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        body {
            background-color: var(--bg-base);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 15% 15%, rgba(99, 102, 241, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 85% 85%, rgba(16, 185, 129, 0.12) 0%, transparent 45%);
            background-attachment: fixed;
        }

        /* Glassmorphism Header */
        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 2rem;
            background: rgba(10, 14, 23, 0.8);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-glass);
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .logo-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 20px var(--primary-glow);
        }

        .logo-icon svg {
            width: 20px;
            height: 20px;
            fill: #fff;
        }

        .brand-title {
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            background: linear-gradient(to right, #fff, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .status-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.25);
            color: #34d399;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #10b981;
            box-shadow: 0 0 10px #10b981;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.3); }
        }

        /* Hero Search Area */
        .hero-section {
            max-width: 1200px;
            margin: 2rem auto 1.5rem;
            padding: 0 1.5rem;
            width: 100%;
        }

        .search-container {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            background: var(--bg-surface);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-glass);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5);
        }

        .search-bar {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .input-wrapper {
            flex: 1;
            position: relative;
            min-width: 280px;
        }

        .input-wrapper svg {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            fill: var(--text-muted);
        }

        input[type="text"] {
            width: 100%;
            padding: 0.9rem 1rem 0.9rem 3rem;
            border-radius: 14px;
            border: 1px solid var(--border-glass);
            background: rgba(10, 14, 23, 0.6);
            color: var(--text-primary);
            font-size: 0.95rem;
            outline: none;
            transition: all 0.25s ease;
        }

        input[type="text"]:focus {
            border-color: var(--primary);
            box-shadow: 0 0 15px var(--primary-glow);
            background: rgba(10, 14, 23, 0.9);
        }

        .btn-primary {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.9rem 1.75rem;
            border-radius: 14px;
            border: none;
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 4px 20px var(--primary-glow);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.5);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .presets {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
            font-size: 0.825rem;
            color: var(--text-muted);
        }

        .preset-chip {
            padding: 0.35rem 0.75rem;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-glass);
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s;
            user-select: none;
        }

        .preset-chip:hover {
            background: rgba(16, 185, 129, 0.15);
            border-color: rgba(16, 185, 129, 0.3);
            color: #34d399;
        }

        /* Dual Column Studio Grid */
        .studio-grid {
            max-width: 1200px;
            margin: 0 auto 3rem;
            padding: 0 1.5rem;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1.15fr;
            gap: 1.5rem;
            flex: 1;
        }

        @media (max-width: 900px) {
            .studio-grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: var(--bg-surface);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-glass);
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5);
        }

        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-glass);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-title {
            font-size: 1.05rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Player Panel */
        .player-wrapper {
            position: relative;
            width: 100%;
            padding-top: 56.25%; /* 16:9 Aspect Ratio */
            background: #000;
        }

        .player-wrapper iframe, .player-wrapper #youtube-player-frame {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }

        /* Audio Stream Player Panel */
        .audio-player-wrapper {
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem 1.5rem;
            background: radial-gradient(circle at center, rgba(30, 41, 59, 0.75) 0%, rgba(10, 14, 23, 0.95) 100%);
            position: relative;
            min-height: 280px;
        }

        .album-art-container {
            position: relative;
            width: 160px;
            height: 160px;
            margin-bottom: 0.75rem;
        }

        .album-art-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6), 0 0 25px rgba(16, 185, 129, 0.35);
            border: 3px solid rgba(255, 255, 255, 0.15);
            transition: transform 0.5s ease;
        }

        .album-art-img.playing {
            animation: spinAlbum 18s linear infinite;
        }

        @keyframes spinAlbum {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .equalizer-wave {
            display: flex;
            align-items: flex-end;
            gap: 4px;
            height: 22px;
            margin: 0.4rem 0;
        }

        .wave-bar {
            width: 4px;
            background: linear-gradient(to top, #10b981, #6366f1);
            border-radius: 2px;
            height: 4px;
            transition: height 0.15s ease;
        }

        .equalizer-wave.active .wave-bar:nth-child(1) { animation: bounceBar 0.8s ease-in-out infinite alternate; }
        .equalizer-wave.active .wave-bar:nth-child(2) { animation: bounceBar 0.5s ease-in-out 0.2s infinite alternate; }
        .equalizer-wave.active .wave-bar:nth-child(3) { animation: bounceBar 1.0s ease-in-out 0.4s infinite alternate; }
        .equalizer-wave.active .wave-bar:nth-child(4) { animation: bounceBar 0.7s ease-in-out 0.1s infinite alternate; }
        .equalizer-wave.active .wave-bar:nth-child(5) { animation: bounceBar 0.9s ease-in-out 0.3s infinite alternate; }

        @keyframes bounceBar {
            0% { height: 4px; }
            100% { height: 22px; }
        }

        .audio-progress-container {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-top: 0.75rem;
        }

        .progress-bar {
            flex: 1;
            -webkit-appearance: none;
            appearance: none;
            height: 6px;
            border-radius: 3px;
            background: rgba(255, 255, 255, 0.1);
            outline: none;
            cursor: pointer;
        }

        .progress-bar::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 10px #10b981;
            cursor: pointer;
        }

        /* Search Results Tray */
        .search-results-tray {
            display: none;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-glass);
        }

        .tray-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .song-results-list {
            display: flex;
            gap: 0.75rem;
            overflow-x: auto;
            padding: 0.5rem 0.25rem 0.75rem;
            scrollbar-width: thin;
        }

        .song-card {
            flex: 0 0 150px;
            background: rgba(18, 24, 38, 0.85);
            border: 1px solid var(--border-glass);
            border-radius: 12px;
            padding: 0.65rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .song-card:hover {
            transform: translateY(-3px);
            border-color: rgba(16, 185, 129, 0.5);
            background: rgba(26, 35, 54, 0.95);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
        }

        .song-card.active {
            border-color: #10b981;
            background: rgba(16, 185, 129, 0.15);
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.3);
        }

        .song-card-img {
            width: 100%;
            aspect-ratio: 1;
            border-radius: 8px;
            object-fit: cover;
            background: #1e293b;
        }

        .song-card-title {
            font-size: 0.825rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #fff;
        }

        .song-card-artist {
            font-size: 0.725rem;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .track-metadata {
            padding: 1.25rem 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            background: rgba(10, 14, 23, 0.4);
            border-top: 1px solid var(--border-glass);
        }

        .track-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #fff;
            line-height: 1.4;
        }

        .track-artist {
            font-size: 0.95rem;
            color: var(--text-secondary);
        }

        .meta-pills {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.25rem;
        }

        .pill {
            padding: 0.25rem 0.65rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .pill-provider {
            background: rgba(99, 102, 241, 0.15);
            color: #818cf8;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }

        .pill-lang {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .pill-cached {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        /* Lyrics Panel */
        .lyrics-container {
            flex: 1;
            overflow-y: auto;
            max-height: 520px;
            padding: 2rem 1.5rem;
            scroll-behavior: smooth;
            position: relative;
        }

        .lyrics-container::-webkit-scrollbar {
            width: 6px;
        }

        .lyrics-container::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        .lyric-line {
            padding: 0.85rem 1rem;
            margin-bottom: 0.5rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: baseline;
            gap: 1rem;
            font-size: 1.05rem;
            color: var(--text-muted);
            border-left: 3px solid transparent;
        }

        .lyric-line:hover {
            background: rgba(255, 255, 255, 0.03);
            color: var(--text-secondary);
        }

        .lyric-line.active {
            color: #ffffff;
            font-size: 1.25rem;
            font-weight: 700;
            background: linear-gradient(90deg, rgba(16, 185, 129, 0.18), transparent);
            border-left-color: var(--primary);
            text-shadow: 0 0 20px rgba(16, 185, 129, 0.6);
            transform: scale(1.02);
        }

        .timestamp-tag {
            font-family: monospace;
            font-size: 0.75rem;
            color: var(--text-muted);
            min-width: 60px;
            opacity: 0.6;
        }

        .lyric-line.active .timestamp-tag {
            color: #34d399;
            opacity: 1;
            font-weight: 600;
        }

        .lyric-text {
            flex: 1;
            line-height: 1.5;
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            min-height: 320px;
            color: var(--text-muted);
            text-align: center;
            gap: 1rem;
            padding: 2rem;
        }

        .empty-state svg {
            width: 48px;
            height: 48px;
            stroke: var(--text-muted);
            opacity: 0.5;
        }

        /* Raw LRC view */
        .raw-lrc-box {
            display: none;
            padding: 1.5rem;
            height: 100%;
            max-height: 520px;
            overflow-y: auto;
            background: #090d16;
            color: #a7f3d0;
            font-family: monospace;
            font-size: 0.9rem;
            line-height: 1.7;
            white-space: pre-wrap;
        }

        .view-toggle {
            display: flex;
            gap: 0.25rem;
            background: rgba(0, 0, 0, 0.4);
            padding: 3px;
            border-radius: 8px;
            border: 1px solid var(--border-glass);
        }

        .toggle-btn {
            background: transparent;
            border: none;
            color: var(--text-muted);
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .toggle-btn.active {
            background: var(--bg-surface-elevated);
            color: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }

        /* Footer */
        footer {
            margin-top: auto;
            text-align: center;
            padding: 1.5rem;
            color: var(--text-muted);
            font-size: 0.85rem;
            border-top: 1px solid var(--border-glass);
            background: rgba(10, 14, 23, 0.6);
        }

        footer a {
            color: #818cf8;
            text-decoration: none;
        }

        footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header>
        <div class="logo-group">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>
            </div>
            <div>
                <h1 class="brand-title">Dhunzo AI Lyrics Studio</h1>
            </div>
        </div>
        <div class="status-badge">
            <span class="pulse-dot"></span>
            <span>Central API Online</span>
        </div>
    </header>

    <!-- Hero Search Bar -->
    <section class="hero-section">
        <div class="search-container">
            <div class="search-bar">
                <div class="input-wrapper">
                    <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                    <input type="text" id="song-input" value="Kesariya" placeholder="যেকোনো গানের নাম বা লিঙ্ক দিন (যেমন: Kesariya, Believer, UP Wali Laiki, Bojhena Shey Bojhena)...">
                </div>
                <button class="btn-primary" id="search-btn">
                    <span>Play & Sync Lyrics</span>
                    <span>⚡</span>
                </button>
            </div>

            <div id="status-notice" style="display:none; padding: 0.6rem 1rem; border-radius: 10px; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); color: #34d399; font-size: 0.85rem;"></div>

            <div class="presets">
                <span>Quick Presets:</span>
                <span class="preset-chip" onclick="handleSearchOrPlay('Kesariya')">❤️ Kesariya (Arijit Singh)</span>
                <span class="preset-chip" onclick="handleSearchOrPlay('Believer')">⚡ Believer (Imagine Dragons)</span>
                <span class="preset-chip" onclick="handleSearchOrPlay('Lollypop Lagelu')">💃 Lollypop Lagelu (Pawan Singh)</span>
                <span class="preset-chip" onclick="handleSearchOrPlay('UP Wali Laiki')">🔥 UP Wali Laiki (Nishant Mukta)</span>
                <span class="preset-chip" onclick="handleSearchOrPlay('Bojhena Shey Bojhena')">🎵 Bojhena Shey Bojhena (Bengali)</span>
            </div>

            <!-- Search Results Horizontal Tray -->
            <div class="search-results-tray" id="search-results-tray" style="display:none;">
                <div class="tray-header">
                    <span>🎵 সার্চ রেজাল্ট (ক্লিক করলেই গান পরিবর্তন হয়ে প্লে হবে):</span>
                    <span id="results-count" style="color: #10b981;"></span>
                </div>
                <div class="song-results-list" id="song-results-list"></div>
            </div>
        </div>
    </section>

    <!-- Studio Grid (Video Player on Left, Synced Lyrics on Right) -->
    <main class="studio-grid">
        <!-- Left: YouTube Video & Audio Stream Player -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <span id="player-mode-icon">🎵</span>
                    <span id="player-mode-title">Live Playback</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.6rem;">
                    <button id="btn-audio-fallback" onclick="triggerAudioFallback()" style="display:none; background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.4); border-radius: 8px; color: #34d399; font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.6rem; cursor: pointer;">
                        🎵 সরাসরি অডিওতে শুনুন
                    </button>
                    <span id="player-time" style="font-family: monospace; font-size: 0.85rem; color: #34d399;">00:00 / 00:00</span>
                </div>
            </div>

            <!-- YouTube Video Wrapper (Mode 1) -->
            <div class="player-wrapper" id="yt-player-box" style="display: none;">
                <div id="youtube-player-frame"></div>
            </div>

            <!-- Audio Stream Wrapper (Mode 2) -->
            <div class="audio-player-wrapper" id="audio-player-box" style="display: flex;">
                <div class="album-art-container">
                    <img id="audio-album-art" src="https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?w=500&auto=format&fit=crop" alt="Album Art" class="album-art-img">
                </div>
                <div class="equalizer-wave" id="audio-wave">
                    <div class="wave-bar"></div>
                    <div class="wave-bar"></div>
                    <div class="wave-bar"></div>
                    <div class="wave-bar"></div>
                    <div class="wave-bar"></div>
                </div>
                <div class="audio-progress-container">
                    <input type="range" class="progress-bar" id="audio-scrubber" value="0" min="0" max="100" step="0.1">
                </div>
                <audio id="html5-audio" preload="auto"></audio>
            </div>

            <!-- Prominent Play & Sync Action Button -->
            <div style="padding: 1rem 1.5rem 0.5rem; display: flex; flex-direction: column; gap: 0.75rem;">
                <button class="btn-primary" id="btn-play-trigger" onclick="playNowAndSync()" style="width: 100%; justify-content: center; font-size: 1.05rem; padding: 1rem;">
                    <span>▶ গান প্লে করুন ও রিয়েল-টাইম লিরিক্স দেখুন</span>
                </button>
                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: #94a3b8;">
                    <span>💡 টিপস: লিরিক্সের যেকোনো লাইনে ক্লিক করলে গান সেখানে চলে যাবে</span>
                    <span id="sync-indicator" style="color: #10b981;">● সিঙ্ক প্রস্তুত</span>
                </div>
            </div>

            <div class="track-metadata">
                <div class="track-title" id="display-title">Kesariya</div>
                <div class="track-artist" id="display-artist">Pritam, Arijit Singh</div>
                <div class="meta-pills" id="meta-pills">
                    <span class="pill pill-provider" id="pill-provider">saavn_hifi</span>
                    <span class="pill pill-lang" id="pill-lang">Lang: HI</span>
                    <span class="pill pill-cached" id="pill-cached">Direct HiFi Audio</span>
                </div>
            </div>
        </div>

        <!-- Right: Real-Time Synced Karaoke Lyrics -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <span>🎤</span>
                    <span>Synchronized LRC Lyrics</span>
                </div>
                <div class="view-toggle">
                    <button class="toggle-btn active" id="btn-karaoke" onclick="switchView('karaoke')">Karaoke</button>
                    <button class="toggle-btn" id="btn-raw" onclick="switchView('raw')">Raw LRC</button>
                </div>
            </div>

            <div class="lyrics-container" id="lyrics-scroll">
                <div class="empty-state" id="empty-state">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 9l10.5-3m0 6.553v3.75a2.25 2.25 0 01-1.632 2.163l-1.32.377a1.803 1.803 0 11-.99-3.467l2.31-.66a2.25 2.25 0 001.632-2.163zm0 0V2.25L9 5.25v10.303m0 0v3.75a2.25 2.25 0 01-1.632 2.163l-1.32.377a1.803 1.803 0 01-.99-3.467l2.31-.66A2.25 2.25 0 009 15.553z" />
                    </svg>
                    <p>গান প্লে করতে ওপরের বাটনে ক্লিক করুন।<br>রিয়েল-টাইমে সিঙ্ক হওয়া লিরিক্স এখানে দেখা যাবে।</p>
                </div>
                <div id="lyrics-lines-container"></div>
            </div>

            <pre class="raw-lrc-box" id="raw-lrc-box"></pre>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <p>Powered by <strong>Dhunzo / BlackHole Music Gateway</strong> • AI Speech-to-Text Fallback Engine • <a href="https://github.com/drodifytechnology/Dhunzo-music" target="_blank">GitHub Repository</a></p>
    </footer>

    <!-- YouTube Iframe API -->
    <script src="https://www.youtube.com/iframe_api"></script>

    <script>
        let ytPlayer = null;
        let isYtReady = false;
        let currentMode = 'audio'; // 'audio' or 'youtube'
        let activeSongData = null;
        let activeLyricIndex = -1;
        let currentLyricsLines = [];
        let timerInterval = null;
        let isScrubbing = false;
        let currentRequestSeq = 0;
        let lastResolvedMeta = { title: '', artist: '' };

        const html5Audio = document.getElementById('html5-audio');
        const audioScrubber = document.getElementById('audio-scrubber');

        // Extract YouTube Video ID from various URL formats
        function extractYouTubeId(url) {
            if (!url) return null;
            const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
            const match = url.match(regExp);
            return (match && match[2].length === 11) ? match[2] : null;
        }

        // HTML5 Audio Event Listeners
        if (html5Audio) {
            html5Audio.addEventListener('play', () => onAudioStateChange(true));
            html5Audio.addEventListener('pause', () => onAudioStateChange(false));
            html5Audio.addEventListener('ended', () => onAudioStateChange(false));
            html5Audio.addEventListener('timeupdate', () => {
                if (currentMode === 'audio') {
                    const cur = html5Audio.currentTime || 0;
                    const dur = html5Audio.duration || (activeSongData ? activeSongData.duration : 0);
                    document.getElementById('player-time').innerText = 
                        formatSeconds(cur) + ' / ' + formatSeconds(dur);

                    if (!isScrubbing && dur > 0) {
                        audioScrubber.value = (cur / dur) * 100;
                    }
                }
            });
            html5Audio.addEventListener('error', (e) => {
                console.warn('Audio playback error:', e);
                const syncInd = document.getElementById('sync-indicator');
                if (syncInd) syncInd.innerHTML = '<span style="color:#f87171">⚠️ অডিও স্ট্রিমে সমস্যা হয়েছে</span>';
            });
        }

        if (audioScrubber) {
            audioScrubber.addEventListener('input', (e) => {
                if (currentMode === 'audio' && html5Audio && html5Audio.duration) {
                    isScrubbing = true;
                    const targetSec = (e.target.value / 100) * html5Audio.duration;
                    html5Audio.currentTime = targetSec;
                    updateLyricsSync();
                }
            });
            audioScrubber.addEventListener('change', () => {
                isScrubbing = false;
            });
        }

        function onAudioStateChange(isPlaying) {
            const playBtn = document.getElementById('btn-play-trigger');
            const syncInd = document.getElementById('sync-indicator');
            const albumArt = document.getElementById('audio-album-art');
            const wave = document.getElementById('audio-wave');

            if (isPlaying) {
                if (playBtn) playBtn.innerHTML = '<span>⏸ পজ করুন</span>';
                if (syncInd) syncInd.innerHTML = '<span style="color:#10b981">● গান বাজছে ও লিরিক্স সিঙ্ক হচ্ছে</span>';
                if (albumArt) albumArt.classList.add('playing');
                if (wave) wave.classList.add('active');
                startSyncLoop();
            } else {
                if (playBtn) playBtn.innerHTML = '<span>▶ গান প্লে করুন ও রিয়েল-টাইম লিরিক্স দেখুন</span>';
                if (syncInd) syncInd.innerHTML = '<span style="color:#94a3b8">○ পজ করা হয়েছে</span>';
                if (albumArt) albumArt.classList.remove('playing');
                if (wave) wave.classList.remove('active');
                stopSyncLoop();
            }
        }

        // YouTube IFrame API Callback
        function onYouTubeIframeAPIReady() {
            isYtReady = true;
            ytPlayer = new YT.Player('youtube-player-frame', {
                height: '100%',
                width: '100%',
                videoId: '8S2YggHf42s',
                playerVars: {
                    'playsinline': 1,
                    'autoplay': 0,
                    'controls': 1,
                    'rel': 0,
                    'modestbranding': 1
                },
                events: {
                    'onStateChange': onYouTubeStateChange,
                    'onError': onPlayerError
                }
            });
        }

        function onPlayerError(event) {
            console.warn('YouTube Player error code:', event.data);
            const syncInd = document.getElementById('sync-indicator');
            if (syncInd) {
                syncInd.innerHTML = '<span style="color:#f87171">⚠️ ইউটিউব প্লেয়ারে সমস্যা (Error ' + event.data + ')</span>';
            }

            // YouTube error 150 or 101 means copyright owner disabled 3rd-party iframe embedding
            if (event.data === 150 || event.data === 101 || event.data === 100 || event.data === 2) {
                showNotice('💡 ইউটিউব কর্তৃপক্ষ এই ভিডিওটি এম্বেড করা সীমাবদ্ধ করেছে (Error ' + event.data + ')। স্বয়ংক্রিয়ভাবে সরাসরি অডিও স্ট্রিমে গানটি বাজানো হচ্ছে...');
                triggerAudioFallback();
            }
        }

        function onYouTubeStateChange(event) {
            if (currentMode !== 'youtube') return;
            const playBtn = document.getElementById('btn-play-trigger');
            const syncInd = document.getElementById('sync-indicator');
            if (event.data === YT.PlayerState.PLAYING) {
                if (playBtn) playBtn.innerHTML = '<span>⏸ পজ করুন</span>';
                if (syncInd) syncInd.innerHTML = '<span style="color:#10b981">● গান বাজছে ও লিরিক্স সিঙ্ক হচ্ছে</span>';
                startSyncLoop();
            } else {
                if (playBtn) playBtn.innerHTML = '<span>▶ গান প্লে করুন ও রিয়েল-টাইম লিরিক্স দেখুন</span>';
                if (syncInd) syncInd.innerHTML = '<span style="color:#94a3b8">○ পজ করা হয়েছে</span>';
                stopSyncLoop();
            }
        }

        function showNotice(msg) {
            const el = document.getElementById('status-notice');
            if (el) {
                el.innerText = msg;
                el.style.display = 'block';
            }
        }

        function hideNotice() {
            const el = document.getElementById('status-notice');
            if (el) {
                el.style.display = 'none';
            }
        }

        // Unified Play / Pause Master Button
        function playNowAndSync() {
            if (currentMode === 'audio') {
                if (html5Audio.paused) {
                    html5Audio.play().then(() => onAudioStateChange(true)).catch(e => {
                        console.warn('Audio play restricted by browser:', e);
                    });
                } else {
                    html5Audio.pause();
                }
            } else if (currentMode === 'youtube') {
                if (!ytPlayer || typeof ytPlayer.getPlayerState !== 'function') return;
                const state = ytPlayer.getPlayerState();
                if (state === YT.PlayerState.PLAYING) {
                    ytPlayer.pauseVideo();
                } else {
                    ytPlayer.playVideo();
                }
            }
        }

        function startSyncLoop() {
            if (timerInterval) clearInterval(timerInterval);
            timerInterval = setInterval(updateLyricsSync, 80);
        }

        function stopSyncLoop() {
            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
            }
        }

        // Real-time synchronization logic
        function updateLyricsSync() {
            let currentTimeSec = 0;
            let durationSec = 0;

            if (currentMode === 'audio') {
                currentTimeSec = html5Audio.currentTime || 0;
                durationSec = html5Audio.duration || (activeSongData ? activeSongData.duration : 0);
            } else if (currentMode === 'youtube') {
                if (ytPlayer && typeof ytPlayer.getCurrentTime === 'function') {
                    currentTimeSec = ytPlayer.getCurrentTime() || 0;
                    durationSec = ytPlayer.getDuration() || 0;
                    document.getElementById('player-time').innerText = 
                        formatSeconds(currentTimeSec) + ' / ' + formatSeconds(durationSec);
                }
            }

            const currentMs = currentTimeSec * 1000;
            if (!currentLyricsLines || currentLyricsLines.length === 0) return;

            // Find current active line
            let matchedIndex = -1;
            for (let i = 0; i < currentLyricsLines.length; i++) {
                const line = currentLyricsLines[i];
                if (currentMs >= line.start_ms && currentMs <= (line.end_ms || line.start_ms + 8000)) {
                    matchedIndex = i;
                    break;
                } else if (currentMs >= line.start_ms) {
                    matchedIndex = i; // Fallback to latest passed line
                }
            }

            if (matchedIndex !== activeLyricIndex && matchedIndex !== -1) {
                highlightLyric(matchedIndex);
            }
        }

        function highlightLyric(index) {
            activeLyricIndex = index;
            const allLines = document.querySelectorAll('.lyric-line');
            allLines.forEach((el, idx) => {
                if (idx === index) {
                    el.classList.add('active');
                    // Smooth auto-scroll to center active line
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    el.classList.remove('active');
                }
            });
        }

        function seekTo(ms) {
            const sec = ms / 1000;
            if (currentMode === 'audio') {
                html5Audio.currentTime = sec;
                if (html5Audio.paused) {
                    html5Audio.play().catch(e => console.warn(e));
                }
            } else if (currentMode === 'youtube') {
                if (ytPlayer && typeof ytPlayer.seekTo === 'function') {
                    ytPlayer.seekTo(sec, true);
                    if (ytPlayer.getPlayerState() !== YT.PlayerState.PLAYING) {
                        ytPlayer.playVideo();
                    }
                }
            }
        }

        // Trigger Audio Fallback when YouTube fails or is requested
        function triggerAudioFallback() {
            const targetTitle = lastResolvedMeta.title || document.getElementById('display-title').innerText || 'Kesariya';
            const targetArtist = lastResolvedMeta.artist || '';
            const query = (targetTitle + ' ' + targetArtist).trim();
            console.log('Switching to audio fallback stream for:', query);
            searchSongsAndAutoPlay(query);
        }

        // Smart Universal Search & Play Controller
        async function handleSearchOrPlay(queryOrUrl) {
            let cleanQuery = (queryOrUrl || '').trim();
            if (!cleanQuery) return;

            // Immediately reflect selected query in the input box!
            document.getElementById('song-input').value = cleanQuery;

            const btn = document.getElementById('search-btn');
            btn.innerHTML = '<span>Searching & Playing...</span> <span>⏳</span>';
            hideNotice();

            // Auto-correct typo for YouTube ID
            if (cleanQuery.includes('DOYkgCt_MgA')) {
                cleanQuery = cleanQuery.replace('DOYkgCt_MgA', 'D0YkgCt_MgA');
                document.getElementById('song-input').value = cleanQuery;
                showNotice('💡 লিঙ্কে ইংরেজি "O" এর বদলে সংখ্যা "0" (D0YkgCt_MgA) কারেক্ট করা হয়েছে।');
            }

            const videoId = extractYouTubeId(cleanQuery);

            if (cleanQuery.startsWith('http://') || cleanQuery.startsWith('https://')) {
                if (videoId) {
                    // YouTube Mode
                    await playYouTubeMode(cleanQuery, videoId);
                } else {
                    // Direct Audio Stream URL Mode
                    playAudioDirectUrl(cleanQuery);
                }
                btn.innerHTML = '<span>Play & Sync Lyrics</span> <span>⚡</span>';
            } else {
                // Song Title / Artist Name Search Query
                await searchSongsAndAutoPlay(cleanQuery);
                btn.innerHTML = '<span>Play & Sync Lyrics</span> <span>⚡</span>';
            }
        }

        // Play YouTube Video Mode
        async function playYouTubeMode(url, videoId) {
            const reqSeq = ++currentRequestSeq;
            currentMode = 'youtube';
            html5Audio.pause();

            document.getElementById('yt-player-box').style.display = 'block';
            document.getElementById('audio-player-box').style.display = 'none';
            document.getElementById('search-results-tray').style.display = 'none';
            document.getElementById('btn-audio-fallback').style.display = 'inline-block';

            document.getElementById('player-mode-icon').innerText = '🎬';
            document.getElementById('player-mode-title').innerText = 'YouTube Video Playback';

            if (videoId && ytPlayer && typeof ytPlayer.loadVideoById === 'function') {
                ytPlayer.loadVideoById(videoId);
            }

            await fetchLyrics('/api/v1/lyrics/search?url=' + encodeURIComponent(url), reqSeq);
        }

        // Direct Audio URL Mode
        function playAudioDirectUrl(url) {
            currentMode = 'audio';
            const reqSeq = ++currentRequestSeq;

            if (ytPlayer && typeof ytPlayer.stopVideo === 'function') {
                ytPlayer.stopVideo();
            }

            document.getElementById('yt-player-box').style.display = 'none';
            document.getElementById('audio-player-box').style.display = 'flex';
            document.getElementById('search-results-tray').style.display = 'none';
            document.getElementById('btn-audio-fallback').style.display = 'none';

            document.getElementById('player-mode-icon').innerText = '🎵';
            document.getElementById('player-mode-title').innerText = 'Direct Audio Stream';

            html5Audio.src = url;
            html5Audio.play().catch(e => console.warn(e));

            fetchLyrics('/api/v1/lyrics/search?audio_url=' + encodeURIComponent(url), reqSeq);
        }

        // Search songs via Central Gateway and Auto-Play #1 Top Match
        async function searchSongsAndAutoPlay(query) {
            const reqSeq = ++currentRequestSeq;
            try {
                const res = await fetch('/api/v1/search?query=' + encodeURIComponent(query));
                const json = await res.json();

                if (reqSeq !== currentRequestSeq) return; // Discard outdated requests

                if (json.success && json.data && json.data.length > 0) {
                    renderSearchResults(json.data, reqSeq);
                    // Automatically auto-play top match!
                    playSelectedSong(json.data[0], reqSeq);
                } else {
                    // If Saavn search has no audio, search lyrics directly
                    document.getElementById('search-results-tray').style.display = 'none';
                    fetchLyrics('/api/v1/lyrics/search?title=' + encodeURIComponent(query), reqSeq);
                }
            } catch (err) {
                console.error(err);
                if (reqSeq === currentRequestSeq) {
                    fetchLyrics('/api/v1/lyrics/search?title=' + encodeURIComponent(query), reqSeq);
                }
            }
        }

        function renderSearchResults(songs, reqSeq) {
            const tray = document.getElementById('search-results-tray');
            const list = document.getElementById('song-results-list');
            const count = document.getElementById('results-count');

            tray.style.display = 'flex';
            count.innerText = songs.length + ' টি গান পাওয়া গেছে';
            list.innerHTML = '';

            songs.forEach((song, idx) => {
                const card = document.createElement('div');
                card.className = 'song-card' + (idx === 0 ? ' active' : '');
                card.id = 'song-card-' + idx;
                card.onclick = () => {
                    document.querySelectorAll('.song-card').forEach(c => c.classList.remove('active'));
                    card.classList.add('active');
                    playSelectedSong(song, ++currentRequestSeq);
                };

                const imgUrl = song.artwork?.medium || song.artwork?.small || 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?w=200';
                const artists = (song.artists || []).map(a => a.name).join(', ') || song.subtitle || 'Artist';

                card.innerHTML = `
                    <img src="${imgUrl}" alt="${song.title}" class="song-card-img" onerror="this.src='https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?w=200'">
                    <div class="song-card-title">${song.title}</div>
                    <div class="song-card-artist">${artists}</div>
                `;

                list.appendChild(card);
            });
        }

        // Play Selected Song from Search with HiFi Audio and Auto-Sync Lyrics
        function playSelectedSong(song, reqSeq) {
            if (reqSeq && reqSeq !== currentRequestSeq) return;
            activeSongData = song;
            currentMode = 'audio';

            if (ytPlayer && typeof ytPlayer.stopVideo === 'function') {
                ytPlayer.stopVideo();
            }

            document.getElementById('yt-player-box').style.display = 'none';
            document.getElementById('audio-player-box').style.display = 'flex';
            document.getElementById('btn-audio-fallback').style.display = 'none';

            document.getElementById('player-mode-icon').innerText = '🎵';
            document.getElementById('player-mode-title').innerText = 'HiFi Audio Stream';

            // Artwork & Metadata
            const artUrl = song.artwork?.large || song.artwork?.medium || song.artwork?.small || 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?w=500';
            document.getElementById('audio-album-art').src = artUrl;
            document.getElementById('display-title').innerText = song.title;
            const artists = (song.artists || []).map(a => a.name).join(', ') || song.subtitle || '';
            document.getElementById('display-artist').innerText = artists;

            lastResolvedMeta = { title: song.title, artist: artists };

            document.getElementById('pill-provider').innerText = song.provider ? song.provider.toUpperCase() + '_HIFI' : 'SAAVN_HIFI';
            document.getElementById('pill-lang').innerText = 'Lang: ' + (song.language || 'hi').toUpperCase();
            document.getElementById('pill-cached').innerText = 'Direct HiFi Audio';

            // Start Audio Playback
            if (song.playback && song.playback.url) {
                html5Audio.src = song.playback.url;
                html5Audio.play().then(() => onAudioStateChange(true)).catch(e => {
                    console.warn('Playback paused pending user gesture:', e);
                    onAudioStateChange(false);
                });
            }

            // Fetch Synced Lyrics
            const primaryArtist = (song.artists && song.artists[0]) ? song.artists[0].name : '';
            let lyricsApiUrl = '/api/v1/lyrics/search?title=' + encodeURIComponent(song.title);
            if (primaryArtist) lyricsApiUrl += '&artist=' + encodeURIComponent(primaryArtist);
            if (song.duration) lyricsApiUrl += '&duration=' + song.duration;
            if (song.playback && song.playback.url) lyricsApiUrl += '&audio_url=' + encodeURIComponent(song.playback.url);

            fetchLyrics(lyricsApiUrl, reqSeq);
        }

        // Fetch Lyrics helper with Request Sequence protection
        async function fetchLyrics(apiUrl, reqSeq) {
            try {
                const res = await fetch(apiUrl);
                const json = await res.json();

                if (reqSeq && reqSeq !== currentRequestSeq) {
                    console.log('Ignored stale lyrics response for request:', reqSeq);
                    return;
                }

                if (json.success && json.data) {
                    renderLyrics(json.data, json.meta, reqSeq);
                } else {
                    showError(json.error?.message || 'Lyrics not found');
                }
            } catch (err) {
                console.error(err);
                if (!reqSeq || reqSeq === currentRequestSeq) {
                    showError('Could not connect to Lyrics API.');
                }
            }
        }

        function renderLyrics(data, meta = {}, reqSeq) {
            if (reqSeq && reqSeq !== currentRequestSeq) return;

            document.getElementById('empty-state').style.display = 'none';
            const container = document.getElementById('lyrics-lines-container');
            container.innerHTML = '';

            currentLyricsLines = data.lines || [];
            activeLyricIndex = -1;

            const resolvedTitle = meta?.title || data.title || (data.extra && data.extra.title);
            const resolvedArtist = meta?.artist || data.artist || (data.extra && data.extra.artist);

            if (resolvedTitle) lastResolvedMeta.title = resolvedTitle;
            if (resolvedArtist) lastResolvedMeta.artist = resolvedArtist;

            // Update Track Metadata Title & Artist if in YouTube mode
            if (currentMode === 'youtube') {
                const titleEl = document.getElementById('display-title');
                const artistEl = document.getElementById('display-artist');

                if (titleEl && resolvedTitle) titleEl.innerText = resolvedTitle;
                if (artistEl && resolvedArtist) artistEl.innerText = resolvedArtist;
            }

            // Update Metadata Header
            document.getElementById('pill-provider').innerText = (data.provider || 'ai_speech_to_text').toUpperCase();
            document.getElementById('pill-lang').innerText = 'LANG: ' + (data.language || 'hi').toUpperCase();
            document.getElementById('pill-cached').innerText = data.extra?.persisted_db ? 'SAVED IN DB (FAST)' : (currentMode === 'audio' ? 'DIRECT HIFI AUDIO' : 'AI TRANSCRIBED');

            // Populate Karaoke Lines
            currentLyricsLines.forEach((item, index) => {
                const lineDiv = document.createElement('div');
                lineDiv.className = 'lyric-line';
                lineDiv.onclick = () => seekTo(item.start_ms);

                const timeSpan = document.createElement('span');
                timeSpan.className = 'timestamp-tag';
                timeSpan.innerText = formatMsToTimestamp(item.start_ms);

                const textSpan = document.createElement('span');
                textSpan.className = 'lyric-text';
                textSpan.innerText = item.text;

                lineDiv.appendChild(timeSpan);
                lineDiv.appendChild(textSpan);
                container.appendChild(lineDiv);
            });

            // Populate Raw LRC box
            document.getElementById('raw-lrc-box').innerText = data.raw || 'No raw LRC format available.';
        }

        function showError(msg) {
            document.getElementById('empty-state').style.display = 'flex';
            document.getElementById('empty-state').innerHTML = `
                <span style="font-size: 2rem;">⚠️</span>
                <p style="color: #f87171;">${msg}</p>
            `;
            document.getElementById('lyrics-lines-container').innerHTML = '';
            document.getElementById('raw-lrc-box').innerText = '';
        }

        function switchView(view) {
            const karaokeBtn = document.getElementById('btn-karaoke');
            const rawBtn = document.getElementById('btn-raw');
            const lyricsScroll = document.getElementById('lyrics-scroll');
            const rawBox = document.getElementById('raw-lrc-box');

            if (view === 'karaoke') {
                karaokeBtn.classList.add('active');
                rawBtn.classList.remove('active');
                lyricsScroll.style.display = 'block';
                rawBox.style.display = 'none';
            } else {
                rawBtn.classList.add('active');
                karaokeBtn.classList.remove('active');
                lyricsScroll.style.display = 'none';
                rawBox.style.display = 'block';
            }
        }

        document.getElementById('search-btn').addEventListener('click', () => {
            const inputVal = document.getElementById('song-input').value.trim();
            if (inputVal) {
                handleSearchOrPlay(inputVal);
            }
        });

        document.getElementById('song-input').addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                const inputVal = document.getElementById('song-input').value.trim();
                if (inputVal) {
                    handleSearchOrPlay(inputVal);
                }
            }
        });

        function formatSeconds(sec) {
            const m = Math.floor(sec / 60);
            const s = Math.floor(sec % 60);
            return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
        }

        function formatMsToTimestamp(ms) {
            const totalSec = ms / 1000;
            const m = Math.floor(totalSec / 60);
            const s = Math.floor(totalSec % 60);
            const cs = Math.floor((ms % 1000) / 10);
            return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s + '.' + (cs < 10 ? '0' : '') + cs;
        }

        window.addEventListener('DOMContentLoaded', () => {
            // Auto-load Kesariya as demonstration
            handleSearchOrPlay('Kesariya');
        });
    </script>
</body>
</html>
