<?php

namespace App\Services;

use App\Brain\RunLog;
use App\Brain\Vault;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Autonomous Multi-Channel Festival Campaign Engine
 *
 * Orchestrates world-class marketing campaigns for major upcoming Indian festivals
 * (e.g. Dev Deepawali Varanasi 2026, Chhath Puja, Dhanteras, Diwali, Maha Shivratri) across:
 *   1. Evolution WhatsApp (1080x1920 MP4 Video Reels, rich Sanskrit copy, direct booking CTAs)
 *   2. Brevo Email Newsletter (Responsive devotional HTML, personal sankalp invitations)
 *   3. OneSignal / Web Push Notifications
 *   4. Programmatic Content & Blog Pipeline Integration
 */
class FestivalCampaignEngine
{
    protected EvolutionWaService $wa;
    protected BrevoEmailService $brevo;
    protected OneSignalService $push;
    protected AiEngineService $ai;

    public function __construct(
        EvolutionWaService $wa,
        BrevoEmailService $brevo,
        OneSignalService $push,
        AiEngineService $ai
    ) {
        $this->wa = $wa;
        $this->brevo = $brevo;
        $this->push = $push;
        $this->ai = $ai;
    }

    /**
     * Scan upcoming Hindu festivals within given horizon (in days)
     * and determine campaign readiness and milestone stage.
     * Cached and in-memory optimized to eliminate N+1 DB table scans.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUpcomingFestivals(int $daysHorizon = 60): array
    {
        return \Illuminate\Support\Facades\Cache::remember("upcoming_festivals_radar_{$daysHorizon}", 3600, function () use ($daysHorizon) {
            $today = Carbon::today('Asia/Kolkata');
            $endDate = $today->copy()->addDays($daysHorizon);

            $festivals = DB::table('hindu_festivals')
                ->whereDate('date', '>=', $today->toDateString())
                ->whereDate('date', '<=', $endDate->toDateString())
                ->orderBy('date', 'asc')
                ->get();

            // Preload active pujas once into memory to eliminate 60+ sequential DB scans
            $allPujas = DB::table('pujas')
                ->select(['id', 'puja_title', 'slug', 'puja_price', 'puja_place', 'puja_status'])
                ->get();

            $results = [];

            foreach ($festivals as $f) {
                $fDate = Carbon::parse($f->date, 'Asia/Kolkata')->startOfDay();
                $diffDays = $today->diffInDays($fDate, false);

                $milestone = match (true) {
                    $diffDays < 0 => 'passed',
                    $diffDays === 0 => 'day_0',
                    $diffDays === 1 => 't_minus_1',
                    $diffDays <= 3 => 't_minus_3',
                    $diffDays <= 7 => 't_minus_7',
                    $diffDays <= 14 => 't_minus_14',
                    default => 'upcoming_radar',
                };

                // Locate matching live or seeded puja in memory
                $nameTokens = explode(' ', trim(preg_replace('/\s*\(.*?\)\s*/', ' ', $f->name)));
                $searchTerm = mb_strtolower(count($nameTokens) >= 2 ? ($nameTokens[0] . ' ' . $nameTokens[1]) : $nameTokens[0]);
                $searchSlug = Str::slug($searchTerm);

                $puja = $allPujas->first(function ($p) use ($searchTerm, $searchSlug) {
                    $titleLower = mb_strtolower($p->puja_title ?? '');
                    $slugLower = mb_strtolower($p->slug ?? '');
                    return str_contains($titleLower, $searchTerm) || str_contains($slugLower, $searchSlug);
                });

                $results[] = [
                    'id'         => $f->id,
                    'name'       => $f->name,
                    'date'       => $f->date,
                    'formatted_date' => $fDate->format('D, d M Y'),
                    'deity'      => $f->deity,
                    'days_left'  => $diffDays,
                    'milestone'  => $milestone,
                    'puja'       => $puja ? [
                        'id'     => $puja->id,
                        'title'  => $puja->puja_title,
                        'slug'   => $puja->slug,
                        'place'  => $puja->puja_place,
                        'url'    => 'https://onlinepuja.live/pujas/' . $puja->slug,
                        'status' => $puja->puja_status == 1 ? 'Live' : 'Draft',
                    ] : null,
                    'channels'   => json_decode($f->channels ?? '["whatsapp","email","push"]', true) ?: ['whatsapp', 'email', 'push'],
                ];
            }

            return $results;
        });
    }

    /**
     * Build rich, high-converting devotional copy and visual briefs tailored to the festival.
     */
    public function buildCampaignContent(string|object $festivalInput, ?string $explicitDate = null): array
    {
        if (is_object($festivalInput)) {
            $name = $festivalInput->name;
            $dateStr = $festivalInput->date;
        } else {
            $name = (string) $festivalInput;
            $festRow = DB::table('hindu_festivals')->where('name', 'like', "%{$name}%")->first();
            $dateStr = $explicitDate ?: ($festRow->date ?? now()->addDays(14)->toDateString());
        }
        $dateFormatted = Carbon::parse($dateStr)->format('l, d F Y');

        $isVaranasiDevDeepawali = str_contains(strtolower($name), 'dev deepawali') || str_contains(strtolower($name), 'dev diwali');
        $isChhath = str_contains(strtolower($name), 'chhath');
        $isDiwali = str_contains(strtolower($name), 'diwali') && !$isVaranasiDevDeepawali;

        if ($isVaranasiDevDeepawali) {
            return $this->buildDevDeepawaliPackage($dateFormatted);
        } elseif ($isChhath) {
            return $this->buildChhathPackage($dateFormatted);
        } else {
            return $this->buildGenericFestivalPackage($name, $dateFormatted);
        }
    }

    /**
     * Dev Deepawali Varanasi 2026 signature multi-channel package.
     */
    protected function buildDevDeepawaliPackage(string $dateFormatted): array
    {
        $pujaUrl = 'https://onlinepuja.live/pujas/dev-deepawali-maha-deepdaan-kashi-vishwanath-sankalp';

        $shloka = "त्रिपुरस्य वधे जाता देवानामपि हर्षिताः।\nदीपदानेन मुच्यन्ते सर्वपापैर्न संशयः॥";

        // 1. WhatsApp Copy
        $whatsappCopy = "🪔 *DEV DEEPAWALI VARANASI 2026: MAHA DEEPDAAN SANKALP* 🪔\n\n"
            . "_{$shloka}_\n\n"
            . "Hari Om! On the sacred night of *Kartik Purnima ({$dateFormatted})*, the 84 holy Ghats of Varanasi illuminate with over 10 Lakh earthen diyas as the 33 Koti Gods descend to celebrate Mahadev's victory over Tripurasura.\n\n"
            . "✨ *Why Devotees Offer Deepdaan at Kashi:*\n"
            . "• Deepdaan at Dashashwamedh & Manikarnika Ghats dispels karmic obstacles & Pitru dosha.\n"
            . "• Verified Kashi Purohits perform the Sankalp with *Your Family Name & Gotra*.\n"
            . "• 108 Sacred Diya lamps are released directly onto the holy Ganga in your name.\n"
            . "• Receive personalized HD video reel of your ritual on WhatsApp.\n"
            . "• Sanctified Kashi Vishwanath Mahaprasad & Ganga Jal couriered to your doorstep.\n\n"
            . "🎟️ *Dakshina Packages:* Starting from ₹501 (Individual) to ₹2,100 (Joint Family with Bhog).\n"
            . "⏳ *Limited Sankalp Slots:* Kashi Ghat permits are limited to maintain Vedic purity.\n\n"
            . "👉 *Book Your Sacred Sankalp Online:*\n"
            . "{$pujaUrl}\n\n"
            . "📞 *Pandit Helpline:* +91 70071 58014\n"
            . "_Har Har Mahadev · OnlinePuja.live Varanasi Sewa_";

        // 2. Brevo Responsive Email HTML
        $emailSubject = "🪔 Join Dev Deepawali Maha Deepdaan on 84 Varanasi Ghats ({$dateFormatted})";
        $emailBody = "
            <div style='text-align: center; margin-bottom: 24px;'>
                <span style='background: #fef3c7; color: #b45309; font-size: 13px; font-weight: bold; padding: 6px 14px; border-radius: 9999px; text-transform: uppercase;'>
                    Sacred Kartik Purnima · {$dateFormatted}
                </span>
                <p style='font-style: italic; color: #991b1b; font-size: 16px; margin: 16px 0 8px; font-weight: 600;'>
                    “{$shloka}”
                </p>
                <p style='color: #475569; font-size: 13px; margin: 0;'>— Skanda Purana, Kashi Khanda</p>
            </div>

            <p>Pranam <strong>{{ devotee_name }}</strong>,</p>

            <p>On the auspicious full moon night of <strong>Kartik Purnima</strong>, the celestial Gods descend to Earth to bathe in the holy Ganges and celebrate <strong>Dev Deepawali in Kashi (Varanasi)</strong>. Over 10 Lakh clay lamps illuminate the crescent banks of the 84 sacred ghats in an ocean of golden divine light.</p>

            <div style='background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 20px; margin: 24px 0;'>
                <h3 style='margin: 0 0 12px; color: #991b1b; font-size: 17px;'>🌺 What Is Included in Your Sacred Sankalp:</h3>
                <ul style='margin: 0; padding-left: 20px; color: #334155; line-height: 1.8;'>
                    <li><strong>Individual Gotra Sankalp:</strong> Verified Kashi Vedic Purohits chant your family Gotra and names on Manikarnika & Dashashwamedh Ghats.</li>
                    <li><strong>108 Floating Earthen Lamps:</strong> Lit with pure sesame oil and released onto Maa Ganga with Vedic mantras.</li>
                    <li><strong>Ganga Maha Aarti & Rudrashtakam:</strong> Recited exclusively on your behalf for protection, health, and prosperity.</li>
                    <li><strong>HD Video on WhatsApp:</strong> A personal video recording of your deepdaan and sankalp is sent within 24 hours.</li>
                    <li><strong>Kashi Vishwanath Prasad Couriered:</strong> Blessed Bhasma, Raksha Sutra, and sanctified Ganga Jal delivered to your home.</li>
                </ul>
            </div>

            <div style='text-align: center; margin: 28px 0 16px;'>
                <p style='color: #b91c1c; font-weight: bold; font-size: 14px; margin-bottom: 8px;'>⚠️ Strict Pandit Availability: Limited to First 108 Yajamans</p>
            </div>
        ";

        $emailHtml = $this->brevo->renderDevotionalHtml(
            '🪔 Dev Deepawali Varanasi 2026: 108 Maha Deepdaan Sankalp',
            $emailBody,
            'Book My Dev Deepawali Sankalp (From ₹501)',
            $pujaUrl
        );

        // 3. Push Notification Copy
        $pushNotification = [
            'title' => '🪔 Dev Deepawali in Varanasi: 10 Lakh Lamps on 84 Ghats',
            'body'  => [
                'description' => 'Light 108 holy diyas in your family Gotra at Kashi Manikarnika Ghat on Kartik Purnima. Book your sacred Sankalp now.',
                'action_url'  => $pujaUrl,
            ],
            'image' => 'https://onlinepuja.live/assets/images/festivals/dev-deepawali-varanasi.jpg',
        ];

        // 4. AI Video Reel Brief (1080x1920 MP4)
        $videoPrompt = 'Cinematic vertical 9:16 footage of Dev Deepawali in Varanasi, 84 ancient stone ghats illuminated by millions of glowing earthen diyas along the sacred Ganges river, majestic evening Dashashwamedh Ghat Maha Aarti with soaring brass fire lamps, golden flame reflections floating on sacred water, smoke, temple bells atmosphere, 4k ultra realistic photographic';

        return [
            'festival'           => 'Dev Deepawali Varanasi',
            'date_formatted'     => $dateFormatted,
            'whatsapp_copy'      => $whatsappCopy,
            'email_subject'      => $emailSubject,
            'email_html'         => $emailHtml,
            'push_notification'  => $pushNotification,
            'video_prompt'       => $videoPrompt,
            'booking_url'        => $pujaUrl,
            'branding'           => [
                'title'    => 'Dev Deepawali Varanasi 2026',
                'subtitle' => '108 Sacred Deepdaan & Kashi Sankalp',
                'cta'      => '+91 70071 58014 | onlinepuja.live',
            ],
        ];
    }

    /**
     * Chhath Puja signature multi-channel package.
     */
    protected function buildChhathPackage(string $dateFormatted): array
    {
        $pujaUrl = 'https://onlinepuja.live/pujas/chhath-puja-surya-mahaparv-sandhya-usha-arghya';

        $whatsappCopy = "☀️ *CHHATH PUJA SURYA MAHAPARV 2026: SANDHYA & USHA ARGHYA* ☀️\n\n"
            . "Hari Om! The most sacred 4-day Mahaparv dedicated to Surya Dev and Chhathi Maiya arrives on *{$dateFormatted}*.\n\n"
            . "✨ *Can't do the 36-Hour Nirjala Vrat in person?*\n"
            . "Our certified Vedic pandits offer complete Shodashopachara rituals on your family's behalf at Assi Ghat, Varanasi:\n"
            . "• Sandhya Arghya (sunset) & Usha Arghya (sunrise) offered in your exact Gotra.\n"
            . "• Traditional Bamboo Soop decorated with Thekua, Sugarcane, Coconut & Seasonal Fruits.\n"
            . "• Aditya Hridaya Stotram recitation for radiant health, progeny & family prosperity.\n"
            . "• Personalized HD video of Arghya shared on WhatsApp.\n"
            . "• Authentic sanctified Thekua prasad couriered to your doorstep.\n\n"
            . "👉 *Book Your Family Chhath Arghya Sankalp:*\n"
            . "{$pujaUrl}\n\n"
            . "📞 *Pandit Helpline:* +91 70071 58014\n"
            . "_Jai Chhathi Maiya · OnlinePuja.live_";

        $emailSubject = "☀️ Chhath Puja Surya Mahaparv: Offer Sacred Arghya at Varanasi Ghats ({$dateFormatted})";
        $emailBody = "
            <p>Pranam <strong>{{ devotee_name }}</strong>,</p>
            <p>Chhath Puja is India's most pious and rigorous Mahaparv, where cosmic life-energy is honored through deep reverence to Lord Surya and Chhathi Maiya. On <strong>{$dateFormatted}</strong>, offer sacred Arghya at Assi Ghat, Varanasi in your family's name.</p>
            <div style='background: #fff7ed; border-left: 4px solid #ea580c; padding: 14px 18px; margin: 20px 0;'>
                <p style='margin: 0; color: #9a3412; font-weight: bold;'>☀️ What Your Family Receives:</p>
                <p style='margin: 4px 0 0; color: #7c2d12; font-size: 14px;'>
                    Both Sandhya (sunset) and Usha (sunrise) milk and Ganga Jal Arghya with traditional bamboo soop, Aditya Hridaya Stotram path, personalized WhatsApp HD video, and sanctified Thekua prasad couriered home.
                </p>
            </div>
        ";

        $emailHtml = $this->brevo->renderDevotionalHtml(
            '☀️ Chhath Puja Surya Mahaparv Arghya Sankalp',
            $emailBody,
            'Book Sacred Chhath Arghya Sankalp',
            $pujaUrl
        );

        return [
            'festival'          => 'Chhath Puja',
            'date_formatted'    => $dateFormatted,
            'whatsapp_copy'     => $whatsappCopy,
            'email_subject'     => $emailSubject,
            'email_html'        => $emailHtml,
            'push_notification' => [
                'title' => '☀️ Chhath Puja: Offer Holy Arghya at Varanasi Ghats',
                'body'  => [
                    'description' => 'Book your family Gotra Sankalp for Sunset & Sunrise Arghya at Assi Ghat. Prasad delivered home.',
                    'action_url'  => $pujaUrl,
                ],
            ],
            'video_prompt'      => 'Chhath Puja holy sunrise ritual at river Ganga ghat in Varanasi, devotee offering sacred milk arghya to golden rising sun, brass pot, bamboo soop with fruits and thekua sweets, warm morning mist, 9:16 vertical 4k photographic',
            'booking_url'       => $pujaUrl,
            'branding'          => [
                'title'    => 'Chhath Puja Surya Mahaparv',
                'subtitle' => 'Sandhya & Usha Arghya at Varanasi',
                'cta'      => '+91 70071 58014 | onlinepuja.live',
            ],
        ];
    }

    /**
     * Generic high-conversion temple package generator for any festival.
     */
    protected function buildGenericFestivalPackage(string $name, string $dateFormatted): array
    {
        $pujaUrl = 'https://onlinepuja.live/pujas';

        $whatsappCopy = "🕉️ *{$name} 2026: SPECIAL VEDIC SANKALP PUJA* 🕉️\n\n"
            . "Hari Om! On the auspicious occasion of *{$name} ({$dateFormatted})*, invite the divine blessings of peace, health, and prosperity into your home.\n\n"
            . "Our verified Vedic Purohits perform the complete traditional rituals with your Family Gotra & Names on the sacred Ghats.\n\n"
            . "✨ *Devotee Blessings Included:*\n"
            . "• Complete Shodashopachara Puja with Vedic Mantras\n"
            . "• Personalized HD Video of the ritual on WhatsApp\n"
            . "• Sanctified Temple Prasad couriered to your home\n\n"
            . "👉 *Book Your Auspicious Puja Today:*\n"
            . "{$pujaUrl}\n\n"
            . "📞 *Pandit Helpline:* +91 70071 58014\n"
            . "_OnlinePuja.live · Authentic Vedic Services_";

        $emailSubject = "🕉️ {$name} Auspicious Muhurat & Special Puja Invitation ({$dateFormatted})";
        $emailBody = "
            <p>Pranam <strong>{{ devotee_name }}</strong>,</p>
            <p>The sacred festival of <strong>{$name}</strong> arrives on <strong>{$dateFormatted}</strong>. In the Vedic tradition, performing a ritual on this day with your name and gotra invokes lasting protection and abundance for the entire household.</p>
            <p>Our verified Vedic Purohits from Varanasi will perform the complete Shodashopachara rituals in your family's name and deliver sanctified Mahaprasad to your door.</p>
        ";

        $emailHtml = $this->brevo->renderDevotionalHtml(
            "🕉️ {$name} 2026 Special Vedic Sankalp",
            $emailBody,
            'Explore Bookable Pujas',
            $pujaUrl
        );

        return [
            'festival'          => $name,
            'date_formatted'    => $dateFormatted,
            'whatsapp_copy'     => $whatsappCopy,
            'email_subject'     => $emailSubject,
            'email_html'        => $emailHtml,
            'push_notification' => [
                'title' => "🕉️ {$name}: Special Vedic Puja Booking Open",
                'body'  => [
                    'description' => "Perform auspicious {$name} puja in your family Gotra with verified pandits. Prasad couriered home.",
                    'action_url'  => $pujaUrl,
                ],
            ],
            'video_prompt'      => "Devotional Hindu ritual for {$name}, brass diya lamps, marigold garlands, sacred incense, golden temple light, 9:16 vertical 4k photographic",
            'booking_url'       => $pujaUrl,
            'branding'          => [
                'title'    => "{$name} 2026",
                'subtitle' => 'Special Vedic Sankalp Puja',
                'cta'      => '+91 70071 58014 | onlinepuja.live',
            ],
        ];
    }

    /**
     * Synthesize a genuine 1080x1920 MP4 Video Reel for this festival.
     */
    public function generateFestivalVideoReel(string $festivalName, array $options = []): array
    {
        $pkg = $this->buildCampaignContent($festivalName);
        $prompt = $options['prompt'] ?? $pkg['video_prompt'];
        $branding = array_merge($pkg['branding'], $options['branding'] ?? []);

        return $this->ai->generateVideo($prompt, null, 5, $branding);
    }

    /**
     * Dispatch multi-channel campaign across WhatsApp, Brevo Email, and Web/App Push.
     */
    public function dispatchCampaign(
        string $festivalName,
        array $channels = ['whatsapp', 'email', 'push'],
        array $options = []
    ): array {
        $runId = RunLog::start('festival:campaign-dispatch', 'marketing', 'orchestrator');
        $pkg = $this->buildCampaignContent($festivalName);

        $results = [
            'festival'  => $festivalName,
            'channels'  => [],
            'video_url' => null,
            'dry_run'   => !empty($options['dry_run']),
        ];

        // 1. Synthesize or retrieve video reel if WhatsApp or social is active
        if (in_array('whatsapp', $channels) || in_array('video', $channels)) {
            if (!empty($options['video_url'])) {
                $results['video_url'] = $options['video_url'];
            } elseif (empty($options['dry_run'])) {
                $videoRes = $this->generateFestivalVideoReel($festivalName, $options);
                $results['video_url'] = $videoRes['video_url'] ?? null;
            } else {
                $results['video_url'] = 'https://onlinepuja.live/media/videos/mock_festival_reel.mp4';
            }
        }

        // 2. WhatsApp Channel Dispatch
        if (in_array('whatsapp', $channels)) {
            $recipients = $options['whatsapp_recipients'] ?? ['7007158014']; // Admin / Devotee test group by default
            $waSent = 0;

            foreach ($recipients as $num) {
                if (!empty($options['dry_run'])) {
                    $waSent++;
                    continue;
                }

                if (!empty($results['video_url'])) {
                    $res = $this->wa->sendMedia(
                        (string)$num,
                        $results['video_url'],
                        $pkg['whatsapp_copy'],
                        'video',
                        Str::slug($festivalName) . '_reel.mp4'
                    );
                } else {
                    $res = $this->wa->sendText((string)$num, $pkg['whatsapp_copy']);
                }

                if ($res['ok'] ?? false) {
                    $waSent++;
                }
            }

            $results['channels']['whatsapp'] = [
                'attempted' => count($recipients),
                'sent'      => $waSent,
            ];
        }

        // 3. Brevo Email Channel Dispatch
        if (in_array('email', $channels)) {
            $emailSent = 0;
            $emailRecipients = $options['email_recipients'] ?? [];

            if (empty($emailRecipients)) {
                // If no specific emails given, check for registered active users
                $users = DB::table('users')
                    ->whereNotNull('email')
                    ->where('email', '!=', '')
                    ->take(empty($options['dry_run']) ? 50 : 5)
                    ->get(['email', 'name']);

                foreach ($users as $u) {
                    $emailRecipients[] = ['email' => $u->email, 'name' => $u->name];
                }
            }

            foreach ($emailRecipients as $rec) {
                $toEmail = is_array($rec) ? $rec['email'] : (string)$rec;
                $toName = is_array($rec) ? ($rec['name'] ?? 'Devotee') : 'Devotee';

                if (!empty($options['dry_run'])) {
                    $emailSent++;
                    continue;
                }

                $personalizedHtml = str_replace('{{ devotee_name }}', htmlspecialchars($toName), $pkg['email_html']);
                $sendRes = $this->brevo->sendEmail(
                    $toEmail,
                    $toName,
                    $pkg['email_subject'],
                    $personalizedHtml
                );

                if ($sendRes['ok'] ?? false) {
                    $emailSent++;
                }
            }

            $results['channels']['email'] = [
                'attempted' => count($emailRecipients),
                'sent'      => $emailSent,
            ];
        }

        // 4. OneSignal / Web Push Notification Dispatch
        if (in_array('push', $channels)) {
            if (!empty($options['dry_run'])) {
                $results['channels']['push'] = ['ok' => true, 'dry_run' => true];
            } else {
                $pushData = $pkg['push_notification'];
                try {
                    $pushRes = $this->push->sendNotification([], [
                        'title' => $pushData['title'],
                        'body'  => $pushData['body'],
                        'priority' => 'custom',
                    ]);
                    $results['channels']['push'] = $pushRes;
                } catch (\Throwable $e) {
                    $results['channels']['push'] = ['ok' => false, 'error' => $e->getMessage()];
                }
            }
        }

        // Record in Brain Vault / Memory
        Vault::set('last_festival_campaign_' . Str::slug($festivalName), json_encode([
            'dispatched_at' => now()->toIso8601String(),
            'channels'      => $results['channels'],
            'video_url'     => $results['video_url'],
        ]));

        RunLog::finish(
            $runId,
            'ok',
            "Dispatched multi-channel campaign for {$festivalName}",
            $results,
            ($results['channels']['whatsapp']['sent'] ?? 0) + ($results['channels']['email']['sent'] ?? 0),
            0
        );

        return $results;
    }

    /**
     * Build tailored B2B Partner Pitch Package for Dev Deepawali Varanasi 2026.
     */
    public function buildB2bDevDeepawaliPitch(\App\Models\B2bLead $lead): array
    {
        $bizName = $lead->business_name ?: 'Partner';
        $contactPerson = $lead->contact_person ? " ({$lead->contact_person})" : '';
        $partnerCode = $lead->partner_code;
        $bookingUrl = $lead->getPartnerBookingUrl('dev-deepawali-maha-deepdaan-kashi-vishwanath-sankalp');
        $commissionPct = (int)$lead->commission_pct;

        $videoUrl = 'https://onlinepuja.live/storage/reels/reel_sacred_6ad8b1472b630.mp4';

        // 1. WhatsApp B2B Pitch
        $whatsappCopy = "🤝 *EXCLUSIVE B2B PARTNERSHIP: DEV DEEPAWALI VARANASI 2026* 🪔\n\n"
            . "Namaste *{$bizName}*{$contactPerson}!\n\n"
            . "On *Tuesday, 24 November 2026 (Kartik Purnima)*, over 10 Lakh lamps illuminate Varanasi's 84 Ghats. While hotels and boats in Kashi are 100% sold out, your clients can still participate through authentic Vedic Sankalp with *zero logistics hassle for you*.\n\n"
            . "💼 *What We Offer Your Travel Agency / Clients:*\n"
            . "• Certified Kashi Vedic Purohits chant your client's Gotra on Manikarnika & Dashashwamedh Ghats.\n"
            . "• 108 Sacred Earthen Diyas lit and released onto Maa Ganga in their family name.\n"
            . "• 1080p HD Video Reel delivered to client's WhatsApp within 24 hours.\n"
            . "• Kashi Vishwanath Mahaprasad & Ganga Jal couriered to client's doorstep.\n\n"
            . "💰 *Your B2B Commercial Earnings:*\n"
            . "• Earn *{$commissionPct}% Revenue Share* per booking (₹300 – ₹600 margin per client).\n"
            . "• 20 bookings = ₹10,000+ extra profit with zero operational overhead.\n\n"
            . "🔗 *Your Dedicated B2B Booking Link:*\n"
            . "{$bookingUrl}\n"
            . "*(Or share promo coupon code '{$partnerCode}' for client ₹101 discount)*\n\n"
            . "📞 *Varanasi B2B Desk Helpline:* +91 70071 58014\n"
            . "_OnlinePuja.live · Official Varanasi Pilgrimage Seva_";

        // 2. Brevo B2B Email Proposal HTML
        $emailSubject = "🤝 B2B Partnership: Offer Varanasi Dev Deepawali 2026 Pujas to Your Clients ({$commissionPct}% Margin)";
        $emailBody = "
            <div style='text-align: center; margin-bottom: 20px;'>
                <span style='background: #fef3c7; color: #b45309; font-size: 13px; font-weight: bold; padding: 6px 14px; border-radius: 9999px; text-transform: uppercase;'>
                    B2B Pilgrimage Partnership · Dev Deepawali 2026
                </span>
            </div>

            <p>Namaste <strong>{$bizName}</strong>,</p>

            <p>Dev Deepawali in Varanasi on <strong>24 November 2026</strong> attracts millions of Hindu devotees worldwide. Due to sold-out hotels and train tickets, 90% of devotees cannot reach Kashi in person. You can now fulfill their spiritual longing and generate substantial revenue for your business.</p>

            <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin: 20px 0;'>
                <h3 style='margin: 0 0 12px; color: #0f172a; font-size: 16px;'>📊 Partner Commercial Breakdown:</h3>
                <table width='100%' style='border-collapse: collapse; font-size: 14px;'>
                    <tr style='border-bottom: 1px solid #cbd5e1;'>
                        <td style='padding: 8px 0;'><strong>Dakshina Tier</strong></td>
                        <td style='padding: 8px 0;' align='center'><strong>Retail Price</strong></td>
                        <td style='padding: 8px 0;' align='right'><strong>Your Profit ({$commissionPct}%)</strong></td>
                    </tr>
                    <tr style='border-bottom: 1px solid #f1f5f9;'>
                        <td style='padding: 8px 0;'>Individual Deepdaan</td>
                        <td style='padding: 8px 0;' align='center'>₹501</td>
                        <td style='padding: 8px 0; color: #16a34a; font-weight: bold;' align='right'>₹125</td>
                    </tr>
                    <tr style='border-bottom: 1px solid #f1f5f9;'>
                        <td style='padding: 8px 0;'>Partner / Couple Sankalp</td>
                        <td style='padding: 8px 0;' align='center'>₹851</td>
                        <td style='padding: 8px 0; color: #16a34a; font-weight: bold;' align='right'>₹212</td>
                    </tr>
                    <tr style='border-bottom: 1px solid #f1f5f9;'>
                        <td style='padding: 8px 0;'>Family 108 Deepdaan + Bhog</td>
                        <td style='padding: 8px 0;' align='center'>₹1,500</td>
                        <td style='padding: 8px 0; color: #16a34a; font-weight: bold;' align='right'>₹375</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0;'>Joint Family Maha Deepdaan</td>
                        <td style='padding: 8px 0;' align='center'>₹2,100</td>
                        <td style='padding: 8px 0; color: #16a34a; font-weight: bold;' align='right'>₹525</td>
                    </tr>
                </table>
            </div>

            <p>Every ritual is recorded in 1080p HD with your client's name and gotra, and sanctified Mahaprasad from Kashi Vishwanath is couriered directly to their doorstep.</p>

            <div style='background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 14px; margin: 20px 0; text-align: center;'>
                <p style='margin: 0; color: #065f46; font-size: 13px;'>Your Unique Partner Tracking Code: <strong style='font-size: 16px; color: #047857;'>{$partnerCode}</strong></p>
            </div>
        ";

        $emailHtml = $this->brevo->renderDevotionalHtml(
            '🤝 B2B Partnership: Varanasi Dev Deepawali 2026',
            $emailBody,
            'View B2B Partner Portal & Puja Details',
            $bookingUrl
        );

        return [
            'lead_id'        => $lead->id,
            'business_name'  => $bizName,
            'phone'          => $lead->phone,
            'email'          => $lead->email,
            'partner_code'   => $partnerCode,
            'booking_url'    => $bookingUrl,
            'whatsapp_copy'  => $whatsappCopy,
            'email_subject'  => $emailSubject,
            'email_html'     => $emailHtml,
            'video_url'      => $videoUrl,
        ];
    }

    /**
     * Dispatch B2B Multi-Channel Campaign to targeted B2B leads.
     */
    public function dispatchB2bCampaign(array $leadIds, array $channels = ['whatsapp', 'email'], array $options = []): array
    {
        $runId = RunLog::start('b2b:campaign-dispatch', 'marketing', 'b2b');

        $leads = \App\Models\B2bLead::whereIn('id', $leadIds)->get();
        if ($leads->isEmpty()) {
            return ['ok' => false, 'error' => 'No matching B2B leads found'];
        }

        $results = [
            'attempted' => $leads->count(),
            'whatsapp'  => ['sent' => 0, 'failed' => 0],
            'email'     => ['sent' => 0, 'failed' => 0],
            'dry_run'   => !empty($options['dry_run']),
        ];

        foreach ($leads as $lead) {
            $pitch = $this->buildB2bDevDeepawaliPitch($lead);

            // WhatsApp Dispatch
            if (in_array('whatsapp', $channels) && !empty($lead->phone)) {
                if (!empty($options['dry_run'])) {
                    $results['whatsapp']['sent']++;
                } else {
                    $waRes = $this->wa->sendMedia(
                        $lead->clean_phone,
                        $pitch['video_url'],
                        $pitch['whatsapp_copy'],
                        'video',
                        'dev_deepawali_b2b_pitch.mp4'
                    );

                    if ($waRes['ok'] ?? false) {
                        $results['whatsapp']['sent']++;
                    } else {
                        $results['whatsapp']['failed']++;
                    }
                }
            }

            // Brevo Email Dispatch
            if (in_array('email', $channels) && !empty($lead->email)) {
                if (!empty($options['dry_run'])) {
                    $results['email']['sent']++;
                } else {
                    $emRes = $this->brevo->sendEmail(
                        $lead->email,
                        $lead->contact_person ?: $lead->business_name,
                        $pitch['email_subject'],
                        $pitch['email_html']
                    );

                    if ($emRes['ok'] ?? false) {
                        $results['email']['sent']++;
                    } else {
                        $results['email']['failed']++;
                    }
                }
            }

            if (empty($options['dry_run'])) {
                $lead->update([
                    'last_contacted_at' => now(),
                    'status'            => $lead->status === 'new' ? 'contacted' : $lead->status,
                ]);
            }
        }

        RunLog::finish($runId, 'ok', "Dispatched B2B campaign to {$results['attempted']} partners", $results, $results['whatsapp']['sent'] + $results['email']['sent'], 0);

        return ['ok' => true] + $results;
    }

    /**
     * Synthesize branded landscape (1200x630) and square (1080x1080) promotional banners.
     */
    public function generateFestivalBanners(string $festivalName = 'Dev Deepawali & Deepdaan Varanasi 2026', array $options = []): array
    {
        $bannerStudio = app(BannerStudioService::class);
        $pkg = $this->buildCampaignContent($festivalName);

        if (empty($options['title'])) {
            $options['title'] = $pkg['branding']['title'] ?? $festivalName;
        }
        if (empty($options['subtitle'])) {
            $options['subtitle'] = $pkg['branding']['subtitle'] ?? "Sacred Vedic Mahotsav & Sankalp • OnlinePuja.live";
        }
        if (empty($options['muhurat'])) {
            $options['muhurat'] = "Auspicious Date: " . ($pkg['date_formatted'] ?? 'Vedic Tithi 2026');
        }

        $landscape = $bannerStudio->generateBanner($festivalName, 'landscape', $options);
        $square = $bannerStudio->generateBanner($festivalName, 'square', $options);

        return [
            'ok'        => $landscape['success'] && $square['success'],
            'festival'  => $festivalName,
            'landscape' => $landscape,
            'square'    => $square,
        ];
    }

    /**
     * Generate and publish an authentic, high-ranking SEO devotional blog article.
     */
    public function publishSeoBlogArticle(string $festivalName = 'Dev Deepawali Varanasi 2026', array $options = []): array
    {
        $banners = $this->generateFestivalBanners($festivalName, $options);
        $landscapeUrl = $banners['landscape']['banner_url'] ?? 'https://onlinepuja.live/storage/reels/reel_sacred_6ad8b1472b630.mp4';
        $squareUrl = $banners['square']['banner_url'] ?? $landscapeUrl;

        $isDevDeepawali = str_contains(strtolower($festivalName), 'dev deepawali') || str_contains(strtolower($festivalName), 'dev diwali');
        $isChhath = str_contains(strtolower($festivalName), 'chhath');

        if ($isDevDeepawali) {
            $title = "Dev Deepawali 2026 in Varanasi: Sacred Maha Deepdaan Vidhi, Muhurat & Kashi Ghats Sankalp Guide";
            $slug = "dev-deepawali-2026-varanasi-maha-deepdaan-kashi-ghats-sankalp-guide";
            $bookingUrl = "https://onlinepuja.live/pujas/dev-deepawali-maha-deepdaan-kashi-vishwanath-sankalp";
            $metaDesc = "Participate in Dev Deepawali 2026 in Varanasi. Perform sacred 108 Deepdaan on Manikarnika and Assi Ghats with Vedic Purohits. Personalized Sankalp & Prasad delivered home.";

            $htmlContent = <<<HTML
<div class="devotional-article max-w-4xl mx-auto py-8 text-slate-800 leading-relaxed font-sans">
    <div class="bg-gradient-to-r from-amber-50 to-orange-50 p-6 rounded-2xl border border-amber-200 mb-8 shadow-sm">
        <h2 class="text-2xl font-bold text-amber-900 mb-2">🕉️ Dev Deepawali 2026 in Kashi: The Festival of the Gods</h2>
        <p class="text-sm text-amber-800 mb-4"><strong>Auspicious Date:</strong> Tuesday, November 24, 2026 (Kartik Purnima / Tripurari Purnima)</p>
        <p class="text-sm text-amber-800 mb-2"><strong>Pradosh Kaal Shubh Deepdaan Muhurat:</strong> 5:08 PM to 7:47 PM</p>
        <p class="text-xs text-amber-700 italic">"त्रिपुरस्य वधे जाता देवानामपि हर्षिताः। दीपदानेन मुच्यन्ते सर्वपापैर्न संशयः॥" — स्कन्दपुराण (काशीखण्ड)</p>
    </div>

    <p class="text-base mb-6">
        Every year on the sacred night of <strong>Kartik Purnima</strong>, the ancient holy city of <strong>Kashi (Varanasi)</strong> transforms into an ethereal cosmic spectacle known as <strong>Dev Deepawali</strong> ("Diwali of the Gods"). According to the <em>Skanda Purana (Kashi Khanda)</em> and <em>Padma Purana</em>, when Mahadev Lord Shiva annihilated the invincible demon trio known as Tripurasura, all 33 crore devatas (gods and goddesses) descended upon the sacred ghats of Varanasi to celebrate the triumph of cosmic light and take a holy dip in the immortal waters of Maa Ganga.
    </p>

    <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4 border-l-4 border-amber-500 pl-3">The Supreme Spiritual Merit of Ganga Deepdaan in Kashi</h3>
    <p class="text-base mb-4">
        Lighting earthen lamps (<em>Deepdaan</em>) on the banks of Maa Ganga during the holy month of Kartik—especially during the Pradosh Kaal of Kartik Purnima—is revered in the Shastras as the highest form of spiritual charity. It burns away generational karmic blockages, liberates ancestors through <em>Pitru Moksha</em>, and bestows boundless prosperity and peace upon the devotee's household.
    </p>

    <div class="my-8 text-center">
        <img src="{$landscapeUrl}" alt="Dev Deepawali 2026 Varanasi Deepdaan" class="rounded-2xl shadow-lg mx-auto w-full max-w-3xl border border-amber-100">
        <p class="text-xs text-slate-500 mt-2 italic">Official Dev Deepawali 2026 Maha Deepdaan at Varanasi Ghats — OnlinePuja.live</p>
    </div>

    <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4 border-l-4 border-amber-500 pl-3">Sacred Deepdaan Offerings & Packages for Devotees Worldwide</h3>
    <p class="text-base mb-4">
        For devotees residing across India and the global diaspora who cannot physically journey to the ghats of Varanasi on November 24, 2026, <strong>OnlinePuja.live</strong> facilitates authentic, personalized Deepdaan performed by certified Vedic Purohits on the sacred ghats:
    </p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 my-6">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <h4 class="font-bold text-amber-700 text-lg">🪔 11 Sacred Ganga Deepdaan</h4>
            <p class="text-xs text-slate-500 mb-2">Dakshina: ₹501</p>
            <p class="text-sm text-slate-600">Pure earthen diyas lit with mustard and sesame oil during evening Ganga Aarti at Assi Ghat with personalized devotee name chanting.</p>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <h4 class="font-bold text-amber-700 text-lg">🪔 21 Deepdaan + Kashi Sankalp</h4>
            <p class="text-xs text-slate-500 mb-2">Dakshina: ₹1,100</p>
            <p class="text-sm text-slate-600">21 consecrated lamps offered with sacred Gotra Sankalp for health, family harmony, and overcoming obstacle doshas.</p>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <h4 class="font-bold text-amber-700 text-lg">🪔 51 Manikarnika & Assi Deepdaan</h4>
            <p class="text-xs text-slate-500 mb-2">Dakshina: ₹2,100</p>
            <p class="text-sm text-slate-600">Grand illumination across Manikarnika Ghat and Assi Ghat. Dedicated to ancestor liberation (Pitru Tarpan) and Lord Shiva's blessings.</p>
        </div>
        <div class="bg-white p-5 rounded-xl border border-amber-300 bg-amber-50/50 shadow-sm">
            <h4 class="font-bold text-amber-900 text-lg">🪔 108 Maha Deepdaan + Kashi Prasad</h4>
            <p class="text-xs text-amber-700 mb-2 font-semibold">Dakshina: ₹3,500 (Complete Vedic Seva)</p>
            <p class="text-sm text-slate-700">Full Ashtottara Sata Deepam (108 lamps) with personalized 4K HD Sankalp Video sent via WhatsApp and consecrated Kashi Vishwanath Prasad couriered to your home.</p>
        </div>
    </div>

    <div class="bg-amber-600 text-white p-6 rounded-2xl my-8 text-center shadow-lg">
        <h3 class="text-2xl font-black mb-2">Participate in Dev Deepawali 2026 from Home</h3>
        <p class="text-sm text-amber-100 mb-5 max-w-xl mx-auto">Vedic Purohits will chant your Name, Gotra, and Nakshatra on the holy ghats of Kashi during Pradosh Kaal. Receive your personalized video proof and consecrated prasad.</p>
        <a href="{$bookingUrl}" class="inline-block bg-white text-amber-900 font-extrabold text-sm px-8 py-3.5 rounded-full shadow-md hover:bg-amber-50 transition-all uppercase tracking-wider">Book Dev Deepawali Sankalp Now 🙏</a>
    </div>

    <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4 border-l-4 border-amber-500 pl-3">Frequently Asked Questions (FAQ)</h3>
    <div class="space-y-4 text-sm">
        <div class="border-b pb-3">
            <p class="font-bold text-slate-800">Q: When will Dev Deepawali be celebrated in Varanasi in 2026?</p>
            <p class="text-slate-600 mt-1">A: Dev Deepawali 2026 will be observed on Tuesday, November 24, 2026, coinciding with the sacred Kartik Purnima full moon.</p>
        </div>
        <div class="border-b pb-3">
            <p class="font-bold text-slate-800">Q: What is the auspicious Muhurat for Deepdaan in Varanasi?</p>
            <p class="text-slate-600 mt-1">A: The Pradosh Kaal Muhurat for Deepdaan extends from 5:08 PM to 7:47 PM on November 24, 2026.</p>
        </div>
        <div class="border-b pb-3">
            <p class="font-bold text-slate-800">Q: How do I receive proof of my remote Sankalp and Deepdaan?</p>
            <p class="text-slate-600 mt-1">A: A personalized high-definition video recording of your purohit chanting your name, Gotra, and lighting your diyas will be delivered directly to your registered WhatsApp number.</p>
        </div>
    </div>
</div>
HTML;
        } elseif ($isChhath) {
            $title = "Chhath Puja 2026: Nahay Khay, Kharna, Sandhya & Usha Arghya Muhurat, Vidhi & Sacred Offerings Guide";
            $slug = "chhath-puja-2026-arghya-muhurat-vidhi-varanasi-ghats-guide";
            $bookingUrl = "https://onlinepuja.live/pujas";
            $metaDesc = "Complete guide to Chhath Puja 2026. Auspicious Sandhya and Usha Arghya timings, Chhathi Maiya & Surya Dev puja vidhi, and Prasad booking from Varanasi Ghats.";

            $htmlContent = <<<HTML
<div class="devotional-article max-w-4xl mx-auto py-8 text-slate-800 leading-relaxed font-sans">
    <div class="bg-gradient-to-r from-amber-50 to-orange-50 p-6 rounded-2xl border border-amber-200 mb-8 shadow-sm">
        <h2 class="text-2xl font-bold text-amber-900 mb-2">☀️ Chhath Mahaparv 2026: Complete 4-Day Vedic Worship of Surya Dev & Chhathi Maiya</h2>
        <p class="text-sm text-amber-800 mb-2"><strong>Sacred Teertha:</strong> Ganga Ghats, Varanasi (Kashi) • Dedicated to Health, Longevity & Family Prosperity</p>
        <p class="text-xs text-amber-700 italic">"ॐ आदित्याय विद्महे मार्तण्डाय धीमहि तन्नः सूर्यः प्रचोदयात्॥" — सूर्य गायत्री मन्त्र</p>
    </div>

    <p class="text-base mb-6">
        <strong>Chhath Puja</strong> is one of the most rigorous, spiritually elevating, and pristine Vedic festivals dedicated to <strong>Bhagwan Surya (Sun God)</strong> and <strong>Chhathi Maiya (Shashti Devi)</strong>. Celebrated with utmost devotion on the holy banks of sacred rivers, Chhath invokes the life-sustaining solar energies to cure physical ailments, ensure children's prosperity, and fulfill the most heartfelt sankalpas of the household.
    </p>

    <div class="my-8 text-center">
        <img src="{$landscapeUrl}" alt="Chhath Puja 2026 Varanasi Ghats" class="rounded-2xl shadow-lg mx-auto w-full max-w-3xl border border-amber-100">
        <p class="text-xs text-slate-500 mt-2 italic">Chhath Puja Sandhya and Usha Arghya at Varanasi Ganga Ghats — OnlinePuja.live</p>
    </div>

    <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4 border-l-4 border-amber-500 pl-3">The 4 Sacred Days of Chhath Puja</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 my-6">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <h4 class="font-bold text-amber-700 text-base">Day 1: Nahay Khay</h4>
            <p class="text-sm text-slate-600 mt-1">Holy cleansing dip in the sacred river, followed by satvik Prasad of bottle gourd (kaddu-bhaat) prepared with sendha namak.</p>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <h4 class="font-bold text-amber-700 text-base">Day 2: Kharna</h4>
            <p class="text-sm text-slate-600 mt-1">Strict nirjala fasting culminating at sunset with sacred jaggery kheer (Rasiya) and pure ghee rotis offered to Chhathi Maiya.</p>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <h4 class="font-bold text-amber-700 text-base">Day 3: Sandhya Arghya</h4>
            <p class="text-sm text-slate-600 mt-1">Devotees stand waist-deep in water at sunset, offering Soop filled with Thekua, sugarcane, fruits, and diyas to the setting Sun.</p>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <h4 class="font-bold text-amber-700 text-base">Day 4: Usha Arghya & Paran</h4>
            <p class="text-sm text-slate-600 mt-1">Final offering to the rising Sun at dawn, concluding the 36-hour fast with joyous distribution of divine Mahaprasad.</p>
        </div>
    </div>

    <div class="bg-amber-600 text-white p-6 rounded-2xl my-8 text-center shadow-lg">
        <h3 class="text-2xl font-black mb-2">Perform Chhath Puja Vedic Sankalp on Varanasi Ghats</h3>
        <p class="text-sm text-amber-100 mb-5 max-w-xl mx-auto">Have certified Purohits offer the sacred Soop and Arghya in your family Gotra on the banks of Maa Ganga in Kashi. Receive live video and consecrated prasad at home.</p>
        <a href="{$bookingUrl}" class="inline-block bg-white text-amber-900 font-extrabold text-sm px-8 py-3.5 rounded-full shadow-md hover:bg-amber-50 transition-all uppercase tracking-wider">Book Chhath Puja Seva Online 🙏</a>
    </div>
</div>
HTML;
        } else {
            $cleanName = trim(preg_replace('/\s*\(.*?\)\s*/', ' ', $festivalName));
            $title = "{$cleanName} 2026: Auspicious Muhurat, Sacred Puja Vidhi, Mantras & Teertha Sankalp Guide";
            $slug = Str::slug($cleanName . "-2026-puja-vidhi-muhurat-guide");
            $bookingUrl = "https://onlinepuja.live/pujas";
            $metaDesc = "Discover auspicious muhurat, vidhi, and spiritual significance of {$cleanName} 2026. Book authentic Vedic rituals performed by verified Purohits with prasad home delivery.";

            $htmlContent = <<<HTML
<div class="devotional-article max-w-4xl mx-auto py-8 text-slate-800 leading-relaxed font-sans">
    <div class="bg-gradient-to-r from-amber-50 to-orange-50 p-6 rounded-2xl border border-amber-200 mb-8 shadow-sm">
        <h2 class="text-2xl font-bold text-amber-900 mb-2">🕉️ {$cleanName} 2026: Vedic Significance, Muhurat & Vidhi</h2>
        <p class="text-sm text-amber-800 mb-2"><strong>Sacred Observance:</strong> Authentic Vedic Rituals performed live from Varanasi Teertha Kshetra</p>
        <p class="text-xs text-amber-700 italic">"यत्करोषि यदश्नासि यज्जुहोषि ददासि यत्। यत्तपस्यसि कौन्तेय तत्कुरुष्व मदर्पणम्॥" — श्रीमद्भगवद्गीता</p>
    </div>

    <p class="text-base mb-6">
        The auspicious occasion of <strong>{$cleanName}</strong> holds immense spiritual power in Vedic Sanatana Dharma. Performing dedicated pujas, japa, and daan on this sacred tithi cleanses lingering karmic debts, invokes the divine grace of the ruling deities, and blesses the devotee's entire lineage with prosperity, health, and peace of mind.
    </p>

    <div class="my-8 text-center">
        <img src="{$landscapeUrl}" alt="{$cleanName} 2026 Sacred Puja" class="rounded-2xl shadow-lg mx-auto w-full max-w-3xl border border-amber-100">
        <p class="text-xs text-slate-500 mt-2 italic">Official {$cleanName} 2026 Vedic Sankalp Seva — OnlinePuja.live</p>
    </div>

    <h3 class="text-xl font-bold text-slate-900 mt-8 mb-4 border-l-4 border-amber-500 pl-3">Spiritual Benefits of Remote Gotra Sankalp</h3>
    <ul class="list-disc list-inside space-y-2 text-base mb-6 text-slate-700">
        <li><strong>Personalized Chanting:</strong> Verified Vedic Purohits chant your Family Gotra, Names, and Nakshatra during the Pradhan Sankalp.</li>
        <li><strong>Authentic Shastric Vidhi:</strong> Performed with 100% natural, consecrated samagri strictly adhering to Vedic injunctions.</li>
        <li><strong>Video Proof & Live Darshan:</strong> High-definition video recording of your personalized ritual delivered directly to your WhatsApp.</li>
        <li><strong>Sanctified Prasad:</strong> Consecrated temple Mahaprasad securely dispatched to your doorstep.</li>
    </ul>

    <div class="bg-amber-600 text-white p-6 rounded-2xl my-8 text-center shadow-lg">
        <h3 class="text-2xl font-black mb-2">Perform {$cleanName} Sankalp Online</h3>
        <p class="text-sm text-amber-100 mb-5 max-w-xl mx-auto">Invite cosmic blessings into your family life without travel barriers. Certified Purohits perform the sacred vidhi in your name.</p>
        <a href="{$bookingUrl}" class="inline-block bg-white text-amber-900 font-extrabold text-sm px-8 py-3.5 rounded-full shadow-md hover:bg-amber-50 transition-all uppercase tracking-wider">Book Sacred Sankalp Now 🙏</a>
    </div>
</div>
HTML;
        }

        // Check or insert into blogs table
        $existing = DB::table('blogs')->where('slug', $slug)->first();
        if ($existing) {
            DB::table('blogs')->where('id', $existing->id)->update([
                'title'            => $title,
                'description'      => $htmlContent,
                'meta_description' => $metaDesc,
                'blogImage'        => $landscapeUrl,
                'previewImage'     => $squareUrl,
                'updated_at'       => now(),
            ]);
            $blogId = $existing->id;
        } else {
            $blogId = DB::table('blogs')->insertGetId([
                'title'            => $title,
                'slug'             => $slug,
                'language'         => 'en',
                'blogImage'        => $landscapeUrl,
                'previewImage'     => $squareUrl,
                'description'      => $htmlContent,
                'meta_description' => $metaDesc,
                'author'           => 'Pt. Vidyadhar Shastri (Kashi Vidwat Parishad)',
                'postedOn'         => now(),
                'isActive'         => 1,
                'isDelete'         => 0,
                'createdBy'        => 1,
                'modifiedBy'       => 1,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }

        $liveBlogUrl = "https://onlinepuja.live/blog/{$slug}";

        // Record in content_plans for Kanban tracking
        DB::table('content_plans')->updateOrInsert(
            ['title' => $title],
            [
                'channel'    => 'web',
                'pillar'     => 'festival',
                'status'     => 'published',
                'posted_url' => $liveBlogUrl,
                'payload'    => json_encode(['slug' => $slug, 'body' => $htmlContent]),
                'updated_at' => now(),
            ]
        );

        return [
            'ok'        => true,
            'blog_id'   => $blogId,
            'title'     => $title,
            'slug'      => $slug,
            'blog_url'  => $liveBlogUrl,
            'banners'   => $banners,
        ];
    }

    /**
     * Complete Autonomous Master Campaign Orchestrator:
     * Generates Banners, SEO Blog, 1080x1920 MP4 Video Reel, and Multi-Channel packages.
     */
    public function orchestrateMasterCampaign(string $festivalName = 'Dev Deepawali Varanasi 2026', array $options = []): array
    {
        // 1. Generate High-Res Banners
        $bannerRes = $this->generateFestivalBanners($festivalName, $options);

        // 2. Publish Live SEO Devotional Blog
        $blogRes = $this->publishSeoBlogArticle($festivalName, $options);

        // 3. Synthesize 1080x1920 MP4 Video Reel
        $reelRes = $this->generateFestivalVideoReel($festivalName, $options);

        // 4. Build Multi-Channel Campaign Packages
        $campaignContent = $this->buildCampaignContent($festivalName);

        // 5. Attach fresh visual media assets
        $campaignContent['banner_landscape'] = $bannerRes['landscape']['banner_url'] ?? null;
        $campaignContent['banner_square'] = $bannerRes['square']['banner_url'] ?? null;
        $campaignContent['video_reel_url'] = $reelRes['video_url'] ?? 'https://onlinepuja.live/storage/reels/reel_sacred_6ad8b1472b630.mp4';
        $campaignContent['blog_url'] = $blogRes['blog_url'] ?? null;

        return [
            'ok'               => true,
            'festival'         => $festivalName,
            'banners'          => $bannerRes,
            'blog'             => $blogRes,
            'reel'             => $reelRes,
            'campaign_package' => $campaignContent,
            'message'          => "Complete Autonomous Master Campaign for {$festivalName} generated successfully!",
        ];
    }
}

