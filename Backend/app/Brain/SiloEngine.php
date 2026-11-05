<?php

namespace App\Brain;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Topic Silo & Cluster Architect Engine.
 * Ported from proprietary VM SEO Brain suite.
 *
 * Implements strict topical authority clustering:
 * Pillar Page (Money/Booking Page) <-> Supporting Cluster Articles <-> Astrologer & Puja remedies.
 * Prevents topic dilution, boosts link equity, and establishes categorical dominance on Google.
 */
class SiloEngine
{
    /**
     * The 5 Core Sacred Silos of OnlinePuja.live
     */
    public const SILOS = [
        'kashi_pilgrimage' => [
            'name' => 'Varanasi & Sacred Kashi Pilgrimage',
            'pillar_url' => '/puja',
            'pillar_title' => 'Varanasi Temple Pujas & Live Ganga Aarti Sankalp',
            'keywords' => ['kashi vishwanath', 'varanasi puja', 'ganga aarti', 'dashashwamedh', 'kal bhairav', 'assi ghat', 'manikarnika', 'rudrabhishek varanasi'],
            'target_route' => 'puja',
        ],
        'dosha_remedies' => [
            'name' => 'Vedic Dosha Shanti & Planetary Remedies',
            'pillar_url' => '/kundli',
            'pillar_title' => 'Free Kundli Analysis & Vedic Dosha Remedies',
            'keywords' => ['manglik dosha', 'kaal sarp', 'pitra dosha', 'sade sati', 'rahu ketu shanti', 'chandal yoga', 'shani dhaiya', 'dosha nivaran'],
            'target_route' => 'kundli',
        ],
        'jyotish_kundli' => [
            'name' => 'Astrology & Kundli Milan Sanctum',
            'pillar_url' => '/astrologer',
            'pillar_title' => 'Consult Verified Vedic Astrologers in Varanasi',
            'keywords' => ['kundli matching', 'gun milan', 'astrologer consultation', 'horoscope prediction', 'rashifal', 'lagna chart', 'kundali online'],
            'target_route' => 'astrologer',
        ],
        'sacred_festivals' => [
            'name' => 'Hindu Panchang & Mahotsav Radar',
            'pillar_url' => '/panchang',
            'pillar_title' => 'Dainik Vedic Panchang, Shubh Muhurat & Hindu Festivals',
            'keywords' => ['ekadashi vrat', 'mahashivratri', 'pradosh vrat', 'amavasya', 'purnima', 'navratri puja', 'dev deepawali', 'choghadiya'],
            'target_route' => 'panchang',
        ],
        'temple_rituals' => [
            'name' => 'Vedic Havans, Chadhawa & Sacred Prasadam',
            'pillar_url' => '/astromall',
            'pillar_title' => 'Sacred Astromall, Sanctified Rudraksha & Temple Chadhawa',
            'keywords' => ['rudrabhishek havan', 'navagraha puja', 'maha mrityunjaya', 'rudraksha', 'siddh yantra', 'kashi prasad delivery', 'chadhawa'],
            'target_route' => 'astromall',
        ],
    ];

    /**
     * Detect which silo an article or query belongs to based on semantic keyword density.
     */
    public static function detectSilo(string $text): string
    {
        $textLower = mb_strtolower($text);
        $bestSilo = 'kashi_pilgrimage';
        $maxMatches = 0;

        foreach (self::SILOS as $key => $silo) {
            $matches = 0;
            foreach ($silo['keywords'] as $kw) {
                if (str_contains($textLower, $kw)) {
                    $matches += 2;
                }
            }
            if ($matches > $maxMatches) {
                $maxMatches = $matches;
                $bestSilo = $key;
            }
        }

        return $bestSilo;
    }

    /**
     * Get the authoritative Pillar Page recommendation for any silo.
     */
    public static function getPillar(string $siloKey): array
    {
        return self::SILOS[$siloKey] ?? self::SILOS['kashi_pilgrimage'];
    }

    /**
     * Compute site-wide silo integrity and link density report.
     *
     * @return array<string,mixed>
     */
    public static function auditSiloHealth(): array
    {
        $report = [];

        foreach (self::SILOS as $key => $silo) {
            $articleCount = 0;
            if (Schema::hasTable('blogs')) {
                $query = DB::table('blogs');
                foreach ($silo['keywords'] as $idx => $kw) {
                    if ($idx === 0) {
                        $query->where('title', 'LIKE', "%{$kw}%");
                    } else {
                        $query->orWhere('title', 'LIKE', "%{$kw}%");
                    }
                }
                $articleCount = $query->count();
            }

            $report[$key] = [
                'silo_name' => $silo['name'],
                'pillar_page' => $silo['pillar_url'],
                'tracked_articles' => $articleCount,
                'status' => $articleCount >= 3 ? 'Healthy Cluster' : 'Needs Content Ramp',
                'target_keywords' => array_slice($silo['keywords'], 0, 4),
            ];
        }

        return $report;
    }
}
