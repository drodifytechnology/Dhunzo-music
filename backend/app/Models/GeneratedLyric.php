<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneratedLyric extends Model
{
    protected $table = 'generated_lyrics';

    protected $fillable = [
        'track_hash',
        'title',
        'artist',
        'language',
        'provider',
        'type',
        'raw_lrc',
        'lines',
        'audio_source_url',
        'confidence_score',
    ];

    protected $casts = [
        'lines'            => 'array',
        'confidence_score' => 'float',
    ];

    /**
     * Compute standardized hash for a track.
     */
    public static function computeHash(string $title, ?string $artist = null): string
    {
        $cleanTitle = mb_strtolower(trim(preg_replace('/[^\p{L}\p{N}]+/u', '', $title)));
        $cleanArtist = $artist ? mb_strtolower(trim(preg_replace('/[^\p{L}\p{N}]+/u', '', $artist))) : '';
        return md5("{$cleanTitle}:{$cleanArtist}");
    }
}
