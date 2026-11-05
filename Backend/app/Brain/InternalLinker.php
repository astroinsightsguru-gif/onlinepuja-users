<?php

namespace App\Brain;

use Illuminate\Support\Facades\DB;

/**
 * Injects contextual internal links into a freshly written article, pointing at
 * related already-published posts. Internal links are one of the highest-ROI,
 * zero-cost SEO levers — they spread authority, keep readers on-site, and give
 * crawlers a map of the topic cluster. Done once at publish time so every new
 * article ships already woven into the site.
 *
 * Safe by construction: it only ever touches text inside <p> elements, never
 * links text that is already inside an anchor, links each target at most once,
 * and caps the total so an article never reads like a link farm.
 */
class InternalLinker
{
    /** Anchor phrases shorter/vaguer than this are skipped — they'd match noise. */
    protected const MIN_PHRASE_LEN = 8;

    /**
     * @return array{html:string,links_added:int,targets:array<int,string>}
     */
    public static function inject(string $html, string $selfSlug, int $max = 4): array
    {
        $html = trim($html);

        if ($html === '') {
            return ['html' => $html, 'links_added' => 0, 'targets' => []];
        }

        $candidates = self::candidates($selfSlug);

        if (! $candidates) {
            return ['html' => $html, 'links_added' => 0, 'targets' => []];
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        // The XML prolog forces UTF-8; NOIMPLIED/NODEFDTD keep it a fragment
        // instead of wrapping it in <html><body>.
        $dom->loadHTML(
            '<?xml encoding="utf-8"?><div id="__root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $textNodes = $xpath->query('//p//text()[not(ancestor::a)]');

        $added = 0;
        $usedUrls = [];
        $targets = [];

        foreach ($textNodes as $node) {
            if ($added >= $max) {
                break;
            }

            $text = $node->nodeValue;

            foreach ($candidates as $c) {
                if ($added >= $max) {
                    break;
                }

                if (isset($usedUrls[$c['url']])) {
                    continue;
                }

                if (! preg_match('/\b'.preg_quote($c['phrase'], '/').'\b/i', $text, $m, PREG_OFFSET_CAPTURE)) {
                    continue;
                }

                $pos = $m[0][1];
                $matchStr = $m[0][0];
                $before = substr($text, 0, $pos);
                $after = substr($text, $pos + strlen($matchStr));

                $frag = $dom->createDocumentFragment();

                if ($before !== '') {
                    $frag->appendChild($dom->createTextNode($before));
                }

                $a = $dom->createElement('a');
                $a->setAttribute('href', $c['url']);
                $a->appendChild($dom->createTextNode($matchStr));
                $frag->appendChild($a);

                if ($after !== '') {
                    $frag->appendChild($dom->createTextNode($after));
                }

                $node->parentNode->replaceChild($frag, $node);
                $added++;
                $usedUrls[$c['url']] = true;
                $targets[] = $c['url'];

                break; // one link per text node keeps them spread out
            }
        }

        if ($added === 0) {
            return ['html' => $html, 'links_added' => 0, 'targets' => []];
        }

        $root = $dom->getElementById('__root');
        $out = '';

        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return ['html' => trim($out), 'links_added' => $added, 'targets' => $targets];
    }

    /**
     * Build the phrase → url map from published posts. The anchor phrase is the
     * head of the title (before the first colon/dash) — that's the natural,
     * clean topic name, e.g. "Online Puja Samagri" from
     * "Online Puja Samagri: Complete Guide …".
     *
     * @return array<int,array{phrase:string,url:string}>
     */
    protected static function candidates(string $selfSlug): array
    {
        $rows = DB::table('blogs')
            ->where('isActive', 1)
            ->where('isDelete', 0)
            ->where('slug', '!=', $selfSlug)
            ->whereNotNull('slug')
            ->orderByDesc('id')
            ->limit(300)
            ->get(['title', 'slug']);

        $out = [];
        $seen = [];

        foreach ($rows as $r) {
            $phrase = trim((string) preg_split('/[:\-–—|(]/u', (string) $r->title)[0]);

            if (mb_strlen($phrase) < self::MIN_PHRASE_LEN || str_word_count($phrase) < 2) {
                continue;
            }

            $key = mb_strtolower($phrase);

            if (isset($seen[$key])) {
                continue; // keep the newest post for a given phrase
            }

            $seen[$key] = true;
            $out[] = ['phrase' => $phrase, 'url' => url('blog/'.$r->slug)];
        }

        // Add authoritative Pillar anchors from the 5 Sacred Silos
        foreach (SiloEngine::SILOS as $silo) {
            foreach ($silo['keywords'] as $kw) {
                if (mb_strlen($kw) >= self::MIN_PHRASE_LEN) {
                    $key = mb_strtolower($kw);
                    if (!isset($seen[$key])) {
                        $seen[$key] = true;
                        $out[] = ['phrase' => ucwords($kw), 'url' => url(ltrim($silo['pillar_url'], '/'))];
                    }
                }
            }
        }

        // Longest phrases first so "Online Puja Samagri" wins over "Online Puja".
        usort($out, fn ($a, $b) => mb_strlen($b['phrase']) <=> mb_strlen($a['phrase']));

        return $out;
    }
}
