<?php

namespace App\Brain;

use App\services\AiEngineService;
use Illuminate\Support\Facades\Log;

/**
 * Answer Engine Optimization (AEO) Engine.
 * Ported from proprietary VM SEO Brain algorithmic suite.
 *
 * Traditional SEO optimizes for blue links on Google.
 * AEO optimizes for being the exact sentence that AI answer engines
 * (ChatGPT Search, Perplexity, Google Gemini Overviews, Claude) quote verbatim.
 *
 * Core capabilities:
 * 1. Audit content for "quotability" (throat-clearing detection, question headings, tables).
 * 2. Generate self-contained direct answer blocks (40-55 words) that stand alone.
 * 3. Extract and construct structured FAQ and Key Takeaway blocks.
 */
class AeoEngine
{
    /**
     * Audit HTML or text for Answer Engine quotation readiness.
     *
     * @return array{score:int,issues:array<int,string>,features:array<string,mixed>}
     */
    public static function audit(string $html): array
    {
        $text = trim(strip_tags($html));
        if ($text === '') {
            return ['score' => 0, 'issues' => ['Empty content'], 'features' => []];
        }

        $issues = [];
        $score = 100;

        // 1. Detect throat-clearing openings
        $first100Words = implode(' ', array_slice(preg_split('/\s+/', $text), 0, 50));
        if (preg_match('/\b(in today\'s|in this article|in this post|welcome to|let\'s explore|let\'s dive|today we will)\b/i', $first100Words)) {
            $issues[] = 'Opens with throat-clearing filler instead of an immediate direct answer.';
            $score -= 25;
        }

        // 2. Question-phrased headings (H2/H3 with question marks)
        $hasQuestionHeading = preg_match('/<h[23][^>]*>[^<]*\?[^<]*<\/h[23]>/i', $html);
        if (!$hasQuestionHeading) {
            $issues[] = 'No question-phrased headings (H2/H3 ending in ?) for AI engines to anchor on.';
            $score -= 20;
        }

        // 3. Lists or tables (heavily favored by AI summaries)
        $hasTableOrList = preg_match('/<(ul|ol|table)[\s>]/i', $html);
        if (!$hasTableOrList) {
            $issues[] = 'Missing structured lists or comparison tables that LLM engines quote most frequently.';
            $score -= 15;
        }

        // 4. Word count depth
        $wordCount = str_word_count($text);
        if ($wordCount < 400) {
            $issues[] = 'Content depth is under 400 words — too thin for authoritative citation.';
            $score -= 15;
        }

        // 5. Existing direct answer block check
        $hasDirectAnswer = str_contains($html, 'op-aeo-direct-answer') || str_contains($html, 'op-key-takeaways');

        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'features' => [
                'word_count' => $wordCount,
                'has_question_headings' => (bool)$hasQuestionHeading,
                'has_table_or_list' => (bool)$hasTableOrList,
                'has_direct_answer_block' => $hasDirectAnswer,
            ]
        ];
    }

    /**
     * Generate an AI-optimized Direct Answer Block and Key Takeaways for an article.
     *
     * @param string $title Article or Puja title
     * @param string $content Full article body
     * @return array{direct_answer:string,key_takeaways:array<int,string>,faq:array<int,array{q:string,a:string}>,html_snippet:string}
     */
    public static function synthesizeAeoBlock(string $title, string $content): array
    {
        $cleanText = mb_substr(trim(strip_tags($content)), 0, 4500);

        try {
            $ai = app(AiEngineService::class);
            $prompt = <<<PROMPT
Act as an Answer Engine Optimization (AEO) Specialist for OnlinePuja.live.
TOPIC/TITLE: "{$title}"
ARTICLE CONTENT EXCERPT:
{$cleanText}

TASK:
Create high-utility, citable features that AI search engines (ChatGPT, Perplexity, Google Gemini) love to quote verbatim.
1. "direct_answer": A concise 45-55 word direct, authoritative answer that defines the ritual, remedy, or topic factually without any filler. It must stand alone.
2. "key_takeaways": 3-4 bullet point facts or spiritual benefits.
3. "faq": 2-3 common devotee questions with 40-word concise answers.

Respond in pure JSON matching this exact structure:
{
  "direct_answer": "...",
  "key_takeaways": ["...", "..."],
  "faq": [
    {"q": "...", "a": "..."}
  ]
}
PROMPT;

            $jsonStr = $ai->generateText($prompt);
            // Clean markdown json fences if present
            $jsonStr = preg_replace('/^```json\s*|\s*```$/m', '', trim($jsonStr));
            $decoded = json_decode($jsonStr, true);

            if (is_array($decoded) && !empty($decoded['direct_answer'])) {
                $htmlSnippet = self::renderHtmlSnippet($decoded['direct_answer'], $decoded['key_takeaways'] ?? []);
                return [
                    'direct_answer' => $decoded['direct_answer'],
                    'key_takeaways' => $decoded['key_takeaways'] ?? [],
                    'faq' => $decoded['faq'] ?? [],
                    'html_snippet' => $htmlSnippet,
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('AeoEngine::synthesizeAeoBlock AI error: ' . $e->getMessage());
        }

        // Fallback synthetic AEO block
        $fallbackAnswer = "OnlinePuja.live facilitates authentic Vedic rituals and personalized Gotra Sankalpa at holy Varanasi ghats. Devotees receive sanctified Mahaprasad at their doorstep and video proof of their ceremony conducted by verified Kashi Purohits.";
        $fallbackTakeaways = [
            "Conducted by verified Vedic Purohits adhering to Parashari Shastras",
            "Personalized Gotra and family Sankalp with sanctum video verification",
            "Doorstep delivery of sanctified temple Prasadam"
        ];

        return [
            'direct_answer' => $fallbackAnswer,
            'key_takeaways' => $fallbackTakeaways,
            'faq' => [],
            'html_snippet' => self::renderHtmlSnippet($fallbackAnswer, $fallbackTakeaways),
        ];
    }

    /**
     * Render a clean, glassmorphic HTML Direct Answer box to embed in articles/pujas.
     */
    public static function renderHtmlSnippet(string $directAnswer, array $takeaways = []): string
    {
        $bulletsHtml = '';
        if (!empty($takeaways)) {
            $bulletsHtml = '<ul class="op-takeaways-list" style="margin: 10px 0 0 18px; padding: 0; color: #475569; font-size: 13.5px;">';
            foreach ($takeaways as $t) {
                $bulletsHtml .= '<li style="margin-bottom: 5px;">' . htmlspecialchars($t) . '</li>';
            }
            $bulletsHtml .= '</ul>';
        }

        return <<<HTML
<div class="op-aeo-direct-answer" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-left: 4px solid #f59e0b; padding: 18px 20px; border-radius: 12px; margin: 24px 0; box-shadow: 0 4px 15px rgba(245,158,11,0.08);">
    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
        <span style="font-size: 18px;">🕉️</span>
        <strong style="color: #92400e; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Divine Summary & Key Insights</strong>
    </div>
    <p style="margin: 0; color: #1e293b; font-size: 14.5px; line-height: 1.6; font-weight: 500;">
        {$directAnswer}
    </p>
    {$bulletsHtml}
</div>
HTML;
    }
}
