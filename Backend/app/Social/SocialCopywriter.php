<?php

namespace App\Social;

use App\AI\ProviderManager;

/**
 * Turns one idea into platform-native copy. The same caption on every network
 * reads as spam and underperforms — Instagram wants warmth + emojis + many
 * hashtags, X wants a punchy line, Facebook a fuller hook, Pinterest a
 * keyword-rich description. One AI call returns all variants; a deterministic
 * fallback keeps posting alive if the model is unavailable.
 */
class SocialCopywriter
{
    /** Hashtags that are always on-brand, used in the fallback. */
    protected const BASE_TAGS = '#OnlinePuja #Puja #Astrology #Vedic #Hindu #Pandit #Devotion #India';

    public function __construct(protected ProviderManager $ai)
    {
    }

    /**
     * @param  string|null  $personaContext  when set, captions are written in
     *   this astrologer's first-person voice instead of the Online Puja brand voice
     * @return array<string,array{caption:string,hashtags:string}>  keyed by platform
     */
    public function variants(string $baseText, string $title = '', ?string $personaContext = null, string $language = 'en'): array
    {
        $platforms = ['instagram', 'facebook', 'x', 'pinterest', 'youtube'];

        $preamble = $personaContext !== null ? <<<TXT
You are writing social media captions in the FIRST-PERSON VOICE of this Vedic astrologer, who
lists their practice on Online Puja (onlinepuja.live), an Indian platform to book verified pandits
for pujas, havans and astrology online:

{$personaContext}

Write as this astrologer speaking to their own followers — warm, personal, devotional, credible.
Never invent prices, rituals or credentials beyond what is stated above. Mention that consultations
and puja bookings are available on Online Puja where it fits naturally. Write for Indian readers in
clear English.
TXT : <<<'TXT'
You are the social media lead for Online Puja (onlinepuja.live), an Indian platform to book
verified pandits for pujas, havans and astrology online. Write platform-native captions for a
single post idea. Warm, devotional, authentic; never invent prices or rituals; write for Indian
readers in clear English.
TXT;

        $shape = <<<'TXT'
Return STRICT JSON with this exact shape:
{"instagram":{"caption":str,"hashtags":str},"facebook":{"caption":str,"hashtags":str},
"x":{"caption":str,"hashtags":str},"pinterest":{"caption":str,"hashtags":str},
"youtube":{"caption":str,"hashtags":str}}
Rules: instagram = 2-4 warm lines + emojis, hashtags = 10-15 relevant tags. facebook = 3-5 lines,
a question or CTA, hashtags = 3-5. x = ONE punchy line under 240 chars, hashtags = 2-3.
pinterest = keyword-rich description (SEO), hashtags = 4-6. youtube = a Shorts title line + 1-2
line description, hashtags = 3-5. Hashtags are space-separated, each starting with #.
TXT;

        // What live engagement has taught us outranks generic best practice.
        // The brief is an empty string until there is enough measured data, so
        // the prompt is unchanged on an empty database.
        try {
            $learned = app(\App\Brain\Learner::class)->copywritingBrief();
        } catch (\Throwable $e) {
            $learned = '';
        }

        // A Hindi article promoted with an English caption loses the audience it
        // was written for — the caption has to match the page it points at.
        $languageRule = $language === 'hi'
            ? "\n\nWRITE EVERY CAPTION IN HINDI, in Devanagari script — natural spoken Hindi, not "
                ."literal translation. Hashtags may stay in English where that is what people "
                ."actually search (#GaneshChaturthi), and may also include Hindi tags."
            : '';

        $system = $preamble."\n\n".$shape.$languageRule.($learned !== '' ? "\n\n".$learned : '');

        $prompt = "Post idea: \"{$baseText}\"".($title ? "\nRelated article: \"{$title}\"" : '');

        $res = $this->ai->chat($system, $prompt, ['json' => true, 'temperature' => 0.8, 'max_tokens' => 900]);

        $j = $res['ok'] ? $this->decode($res['text']) : [];

        $out = [];

        foreach ($platforms as $p) {
            $caption = trim((string) ($j[$p]['caption'] ?? ''));
            $hashtags = trim((string) ($j[$p]['hashtags'] ?? ''));

            $out[$p] = [
                'caption' => $caption !== '' ? $caption : $this->fallbackCaption($p, $baseText),
                'hashtags' => $hashtags !== '' ? $hashtags : $this->fallbackTags($p),
            ];
        }

        return $out;
    }

    protected function fallbackCaption(string $platform, string $baseText): string
    {
        return match ($platform) {
            'x' => \Illuminate\Support\Str::limit($baseText, 140) . "\n\n📲 Devotee App: https://onlinepuja.live/downloads/onlinepuja-customer-latest.apk",
            'instagram' => "🙏 {$baseText}\n\n📲 Book verified pandits on OnlinePuja Devotee App: https://onlinepuja.live/downloads/onlinepuja-customer-latest.apk\n⭐ Astrologers & Pandits Join: https://play.google.com/store/apps/details?id=live.onlinepuja.astro",
            default => $baseText . "\n\n📲 Book verified pandits online: https://onlinepuja.live/downloads/onlinepuja-customer-latest.apk\n⭐ Pandit / Astrologer Partner App: https://play.google.com/store/apps/details?id=live.onlinepuja.astro 🙏",
        };
    }

    protected function fallbackTags(string $platform): string
    {
        return match ($platform) {
            'x' => '#OnlinePuja #Astrology',
            'instagram' => self::BASE_TAGS.' #Spirituality #Temple #Mantra #Bhakti #PujaSamagri #Kundli #Havan',
            default => self::BASE_TAGS,
        };
    }

    protected function decode(string $text): array
    {
        $clean = trim(preg_replace('/```(?:json)?|```/i', '', $text));
        $j = json_decode($clean, true);

        if (is_array($j)) {
            return $j;
        }

        $s = strpos($clean, '{');
        $e = strrpos($clean, '}');

        if ($s !== false && $e !== false && $e > $s) {
            $j = json_decode(substr($clean, $s, $e - $s + 1), true);
        }

        return is_array($j) ? $j : [];
    }
}
