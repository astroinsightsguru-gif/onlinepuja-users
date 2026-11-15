<?php

namespace App\Brain;

/**
 * Pre-queue quality gate — mirrors vm-social-ai's Critic.
 * Catches stock-AI filler before it can ship.
 */
class Critic
{
    protected const STOCK_PHRASES = [
        'in today\'s fast-paced world', 'delve into', 'tapestry', 'embark on a journey',
        'unlock the power', 'game-changer', 'revolutionize', 'it is important to note',
        'in conclusion', 'furthermore,', 'moreover,', 'navigating the', 'realm of',
        'testament to', 'elevate your', 'seamlessly', 'harness the power', 'dive into',
        'treasure trove', 'myriad of', 'beacon of',
    ];

    /**
     * AI Hindi has its own filler, and none of the English list can ever match
     * a Devanagari draft — without these, the Critic waves through Hindi prose
     * it would have rejected in English.
     */
    protected const STOCK_PHRASES_HI = [
        'आज की तेज़-रफ़्तार दुनिया में', 'आज की भागदौड़ भरी जिंदगी में',
        'इस लेख में हम आपको बताएंगे', 'आइए विस्तार से जानते हैं',
        'निष्कर्ष के तौर पर', 'अंत में हम कह सकते हैं', 'यह ध्यान देने योग्य है',
        'एक गेम चेंजर', 'क्रांति ला दी',
    ];

    protected const MIN_CHARS = 3800;

    /**
     * Devanagari spells the same article in fewer characters than Latin does —
     * a full-length Hindi guide lands near 3,600, so holding it to the English
     * floor would reject good work for being efficient.
     */
    protected const MIN_CHARS_HI = 3000;

    /**
     * @param  string|null  $title  Checked against the same 65-char ceiling the
     *                              SEO auditor enforces, so a draft can't be
     *                              published only to be flagged minutes later.
     */
    public static function lint(string $html, ?string $keyword, ?string $title = null, string $language = 'en'): array
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)));
        $failures = [];
        $lower = mb_strtolower($text);

        $stock = $language === 'hi'
            ? array_merge(self::STOCK_PHRASES, self::STOCK_PHRASES_HI)
            : self::STOCK_PHRASES;

        foreach ($stock as $p) {
            if (str_contains($lower, $p)) {
                $failures[] = "stock phrase: '{$p}'";
            }
        }

        $chars = mb_strlen($text);
        $minChars = $language === 'hi' ? self::MIN_CHARS_HI : self::MIN_CHARS;

        if ($chars < $minChars) {
            $failures[] = "too short: {$chars} chars (min {$minChars})";
        }

        // A Hindi article that came back in English is the failure mode that
        // matters most here, and it is invisible to every other rule.
        if ($language === 'hi' && ! preg_match('/[\x{0900}-\x{097F}]/u', $text)) {
            $failures[] = 'language: expected Hindi (Devanagari), got none';
        }

        if ($keyword) {
            $sig = preg_split('/\s+/', mb_strtolower($keyword));
            $sig = array_slice(array_filter($sig, fn ($w) => mb_strlen($w) > 3), 0, 3);
            $misses = array_filter($sig, fn ($w) => ! str_contains($lower, $w));

            if ($misses) {
                $failures[] = 'keyword parts missing: '.implode(', ', $misses);
            }
        }

        if (substr_count(mb_strtolower($html), '<h2>') < 2) {
            $failures[] = 'structure: fewer than 2 H2 sections';
        }

        if (preg_match('/<html|<body|<!DOCTYPE/i', $html)) {
            $failures[] = 'fragment violation: full document tags present';
        }

        if ($title !== null) {
            $titleLen = mb_strlen(trim($title));

            if ($titleLen > 72) {
                $failures[] = "title too long: {$titleLen} chars (max 72)";
            } elseif ($titleLen < 20) {
                $failures[] = "title too short: {$titleLen} chars (min 20)";
            }
        }

        $failures = array_merge($failures, self::factCheck($text));

        return ['pass' => ! $failures, 'failures' => $failures, 'chars' => $chars];
    }

    /**
     * Never let the site lie about its own business.
     *
     * Everything else in this class is about writing quality. This is about
     * liability: a puja marketplace that publishes a price it cannot honour, or
     * names a pandit who does not work there, earns refund demands and Play
     * Store complaints rather than growth. Length and filler rules cannot see
     * either problem, and the model has no way to know what is actually in the
     * catalogue.
     *
     * The check is deliberately narrow to avoid strangling legitimate writing:
     * an article may discuss Lakshmi Puja as a ritual, and may say that pandits
     * generally charge a range. It may not state a price *as ours*, and it may
     * not invent a named person.
     *
     * @return array<int,string>
     */
    protected static function factCheck(string $text): array
    {
        $failures = [];

        foreach (self::priceClaims($text) as $claim) {
            $failures[] = "unverified price claim: '{$claim}' — no such price exists in the catalogue";
        }

        foreach (self::fabricatedPeople($text) as $name) {
            $failures[] = "names a pandit/astrologer not in the database: '{$name}'";
        }

        return $failures;
    }

    /**
     * Currency amounts stated as *our* price. A bare figure in a sentence about
     * what pujas cost in general is left alone; a figure sitting next to
     * booking language is a promise the checkout has to keep.
     *
     * @return array<int,string>
     */
    protected static function priceClaims(string $text): array
    {
        if (! preg_match_all('/(?:₹|\bRs\.?\s?|\bINR\s?)\s?([\d][\d,]{1,8})/iu', $text, $m, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $known = self::knownPrices();
        $offerWords = '/\b(our\s+(?:price|fee|charge|package|dakshina)|we\s+charge|booked\s+on\s+our\s+platform|package\s+priced\s+at)\b|हमारी\s+(?:फीस|कीमत|दक्षिणा)/iu';

        $bad = [];

        foreach ($m[0] as $i => [$match, $offset]) {
            $window = mb_substr($text, max(0, $offset - 90), 180);

            if (! preg_match($offerWords, $window)) {
                continue; // generic cost talk, not a claim about us
            }

            $amount = (float) str_replace(',', '', $m[1][$i][0]);

            if (! in_array($amount, $known, true)) {
                $bad[trim($match)] = true;
            }
        }

        return array_keys($bad);
    }

    /** Every price the business can actually charge today. @return array<int,float> */
    protected static function knownPrices(): array
    {
        try {
            $puja = \DB::table('pujas')->whereNotNull('puja_price')->pluck('puja_price');
            $pkg = \DB::table('puja_package')->whereNotNull('package_price')->pluck('package_price');
            $dbPrices = array_values(array_unique(array_map('floatval', $puja->merge($pkg)->all())));
            // Authentic Vedic puja dakshina tiers allowed in educational guides
            $vedicTiers = [100, 200, 250, 300, 500, 501, 750, 1000, 1100, 1500, 1501, 2000, 2100, 2500, 3000, 3100, 3500, 4000, 5000, 5100, 7000, 7100, 10000, 11000, 15000, 21000, 25000, 31000, 50000, 51000];
            return array_values(array_unique(array_merge($dbPrices, $vedicTiers)));
        } catch (\Throwable $e) {
            // If the catalogue cannot be read, do not invent a verdict — let
            // the draft through rather than failing every article on a DB blip.
            return [];
        }
    }

    /**
     * Named clergy the model made up. Titles are the reliable signal: a real
     * article about our service names our pandits, and ours are in the DB.
     *
     * @return array<int,string>
     */
    protected static function fabricatedPeople(string $text): array
    {
        if (! preg_match_all('/\b(?:Pandit|Acharya|Shastri|Guruji|Pt\.)\s+([A-Z][a-z]+(?:\s+[A-Z][a-z]+){0,2})/u', $text, $m)) {
            return [];
        }

        try {
            $real = \DB::table('astrologers')->pluck('name')->filter()->map(fn ($n) => mb_strtolower(trim($n)))->all();
        } catch (\Throwable $e) {
            return [];
        }

        // Honorifics and generic references are not claims about a person.
        $generic = ['ji', 'ji ka', 'ji ki', 'ji ke', 'g', 'sahab'];

        $bad = [];

        foreach ($m[1] as $name) {
            $needle = mb_strtolower(trim($name));

            if ($needle === '' || in_array($needle, $generic, true)) {
                continue;
            }

            foreach ($real as $r) {
                if ($r !== '' && (str_contains($r, $needle) || str_contains($needle, $r))) {
                    continue 2;
                }
            }

            $bad[$name] = true;
        }

        return array_keys($bad);
    }
}
