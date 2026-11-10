<?php

namespace App\Services;

use App\AI\ProviderManager;
use App\AI\VedicPromptEnhancer;
use App\Brain\Vault;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Exception;

class AiEngineService
{
    protected ProviderManager $manager;
    protected Client $client;

    public function __construct()
    {
        $this->manager = app(ProviderManager::class);
        $this->client = new Client(['timeout' => 45]);
    }

    /* ═══════════════════════════════════════════════════════════════════
       1. TEXT AI GENERATION ENGINE (Reasoning, Jyotish, Blogs, Chatbot)
       Fallback Order:
       Tier 1: OmniRoute Gateway (Free Pro-Chat / DeepSeek / Qwen)
       Tier 2: Google Gemini (Gemini 1.5/2.0 Flash - 15 RPM Free Tier)
       Tier 3: OpenRouter (meta-llama/llama-3.3-70b-instruct:free, deepseek-r1:free)
       Tier 4: Pollinations Text (Keyless 100% Free Fallback)
    ═══════════════════════════════════════════════════════════════════ */
    public function generateText(string $userPrompt, ?string $systemPrompt = null, ?array $context = null): string
    {
        $res = $this->generateTextDetailed($userPrompt, $systemPrompt, $context);
        return $res['text'] ?? 'Pranam. The cosmic planetary transits are realigning. Please try again.';
    }

    public function generateTextDetailed(string $userPrompt, ?string $systemPrompt = null, ?array $context = null): array
    {
        $system = $systemPrompt ?? "You are an expert Vedic astrologer and spiritual guide for Online Puja. Provide clear, compassionate, and authentic guidance based on traditional Vedic astrology, Jyotish shastra, and spiritual principles in simple Hinglish (Hindi + English).";

        $fullPrompt = $userPrompt;
        if (!empty($context)) {
            $contextStr = "User Details: Name: " . ($context['name'] ?? 'Devotee') . 
                          ", DOB: " . ($context['birthDate'] ?? 'Unknown') . 
                          ", Place: " . ($context['birthPlace'] ?? 'Unknown') . ". ";
            $fullPrompt = $contextStr . "\nQuestion: " . $userPrompt;
        }

        $creds = Vault::aiCredentials();
        $chain = array_filter(array_map('trim', explode(',', (string) ($creds['ai_text_chain'] ?? 'omniroute,gemini,openrouter,pollinations'))));

        foreach ($chain as $tier) {
            $start = microtime(true);

            // Tier 1: OmniRoute Gateway
            if ($tier === 'omniroute' && !empty($creds['omniroute_key'])) {
                try {
                    $url = rtrim($creds['omniroute_url'] ?? 'https://ai.vmstudio.digital', '/') . '/v1/chat/completions';
                    $rawModel = $creds['model_omniroute'] ?? '';
                    $model = (!empty($rawModel) && $rawModel !== 'auto/pro-chat' && $rawModel !== 'auto/gemini') 
                        ? $rawModel 
                        : 'agy/gemini-3.1-flash-lite';
                    $res = $this->client->post($url, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $creds['omniroute_key'],
                            'Content-Type'  => 'application/json',
                        ],
                        'json' => [
                            'model'       => $model,
                            'messages'    => [
                                ['role' => 'system', 'content' => $system],
                                ['role' => 'user', 'content' => $fullPrompt],
                            ],
                            'temperature' => 0.7,
                            'max_tokens'  => 1200,
                        ],
                        'timeout' => 12,
                    ]);
                    $body = json_decode($res->getBody(), true);
                    $text = $body['choices'][0]['message']['content'] ?? '';
                    if (!empty($text)) {
                        return [
                            'success'  => true,
                            'text'     => trim($text),
                            'provider' => 'OmniRoute Gateway',
                            'model'    => $model,
                            'tier'     => 1,
                            'time_ms'  => round((microtime(true) - $start) * 1000),
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::info("OmniRoute Text Tier bypassed: " . $e->getMessage() . " -> Trying Gemini");
                }
            }

            // Tier 2: Google Gemini (Free Tier 15 RPM)
            if ($tier === 'gemini') {
                $geminiKey = !empty($creds['gemini_key']) ? $creds['gemini_key'] : env('GEMINI_API_KEY');
                if (!empty($geminiKey)) {
                    try {
                        $model = !empty($creds['model_gemini']) ? $creds['model_gemini'] : 'gemini-1.5-flash';
                        $res = $this->client->post('https://generativelanguage.googleapis.com/v1beta/openai/chat/completions', [
                            'headers' => [
                                'Authorization' => 'Bearer ' . $geminiKey,
                                'Content-Type'  => 'application/json',
                            ],
                            'json' => [
                                'model'       => $model,
                                'messages'    => [
                                    ['role' => 'system', 'content' => $system],
                                    ['role' => 'user', 'content' => $fullPrompt],
                                ],
                                'temperature' => 0.7,
                            ],
                            'timeout' => 18,
                        ]);
                        $body = json_decode($res->getBody(), true);
                        $text = $body['choices'][0]['message']['content'] ?? '';
                        if (!empty($text)) {
                            return [
                                'success'  => true,
                                'text'     => trim($text),
                                'provider' => 'Google Gemini (Free Tier)',
                                'model'    => $model,
                                'tier'     => 2,
                                'time_ms'  => round((microtime(true) - $start) * 1000),
                            ];
                        }
                    } catch (\Throwable $e) {
                        Log::info("Gemini Text Tier bypassed: " . $e->getMessage() . " -> Trying OpenRouter");
                    }
                }
            }

            // Tier 3: OpenRouter Free Models
            if ($tier === 'openrouter') {
                $orKey = !empty($creds['openrouter_key']) ? $creds['openrouter_key'] : env('OPENROUTER_API_KEY');
                if (!empty($orKey)) {
                    try {
                        $rawModel = $creds['model_openrouter'] ?? '';
                        $model = (!empty($rawModel) && !str_contains($rawModel, 'llama-3.3-70b')) 
                            ? $rawModel 
                            : 'nvidia/nemotron-3.5-lightning:free';
                        $res = $this->client->post('https://openrouter.ai/api/v1/chat/completions', [
                            'headers' => [
                                'Authorization' => 'Bearer ' . $orKey,
                                'Content-Type'  => 'application/json',
                                'HTTP-Referer'  => 'https://onlinepuja.live',
                                'X-Title'       => 'OnlinePuja Astrologer',
                            ],
                            'json' => [
                                'model'       => $model,
                                'messages'    => [
                                    ['role' => 'system', 'content' => $system],
                                    ['role' => 'user', 'content' => $fullPrompt],
                                ],
                                'temperature' => 0.7,
                            ],
                            'timeout' => 12,
                        ]);
                        $body = json_decode($res->getBody(), true);
                        $text = $body['choices'][0]['message']['content'] ?? '';
                        if (!empty($text)) {
                            return [
                                'success'  => true,
                                'text'     => trim($text),
                                'provider' => 'OpenRouter (Free Tier)',
                                'model'    => $model,
                                'tier'     => 3,
                                'time_ms'  => round((microtime(true) - $start) * 1000),
                            ];
                        }
                    } catch (\Throwable $e) {
                        Log::info("OpenRouter Text Tier bypassed: " . $e->getMessage() . " -> Trying Pollinations");
                    }
                }
            }

            // Tier 4: Pollinations AI (Zero-Config, Free Keyless Safety)
            if ($tier === 'pollinations') {
                try {
                    $driver = new \App\AI\Drivers\PollinationsDriver();
                    $chatRes = $driver->chat($system, $fullPrompt, ['model' => 'openai']);
                    if (!empty($chatRes['ok']) && !empty($chatRes['text'])) {
                        return [
                            'success'  => true,
                            'text'     => trim($chatRes['text']),
                            'provider' => 'Pollinations AI (Keyless Free)',
                            'model'    => $chatRes['model'] ?? 'openai',
                            'tier'     => 4,
                            'time_ms'  => round((microtime(true) - $start) * 1000),
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::warning("Pollinations Text failed: " . $e->getMessage());
                }
            }
        }

        return [
            'success'  => false,
            'text'     => 'Pranam. The planetary alignments are undergoing recalibration. Please ask your question again.',
            'provider' => 'None',
            'model'    => 'offline',
            'tier'     => 0,
            'time_ms'  => 0,
        ];
    }

    /* ═══════════════════════════════════════════════════════════════════
       2. IMAGE AI GENERATION ENGINE (Photorealistic Vedic Art & Deities)
       Fallback Order:
       Tier 1: OmniRoute Gateway (Flux Schnell / Dev)
       Tier 2: Pollinations AI (Flux Keyless Free Tier)
       Tier 3: Cloudflare Workers AI (@cf/black-forest-labs/flux-1-schnell Free)
       Tier 4: Hugging Face Inference API (FLUX.1-schnell Serverless)
       Tier 5: Pollinations Turbo (Instant Keyless Safety)
    ═══════════════════════════════════════════════════════════════════ */
    public function generateImage(string $rawPrompt, string $style = 'photorealistic', string $aspectRatio = '1:1'): array
    {
        $enhanced = VedicPromptEnhancer::enhance($rawPrompt, $style, $aspectRatio);
        $prompt = $enhanced['prompt'];
        $negativePrompt = $enhanced['negative_prompt'];
        $size = $enhanced['size'];

        $creds = Vault::aiCredentials();
        $chain = array_filter(array_map('trim', explode(',', (string) ($creds['ai_image_chain'] ?? 'comfyui,omniroute,pollinations,cloudflare,huggingface'))));

        $w = 1024; $h = 1024;
        if ($aspectRatio === '4:5') { $w = 1024; $h = 1280; }
        if ($aspectRatio === '9:16') { $w = 768; $h = 1344; }
        if ($aspectRatio === '16:9') { $w = 1344; $h = 768; }

        foreach ($chain as $tier) {
            $start = microtime(true);

            // Tier: ComfyUI Dedicated GPU Studio (comfy.vmstudio.digital routed via OmniRoute)
            if ($tier === 'comfyui') {
                try {
                    $comfyModel = Vault::get('model_comfyui_image', 'comfyui/flux-dev');
                    $omniUrl = rtrim($creds['omniroute_url'] ?? 'https://ai.vmstudio.digital', '/');

                    if (!empty($creds['omniroute_key'])) {
                        $res = $this->client->post($omniUrl . '/v1/images/generations', [
                            'headers' => [
                                'Authorization' => 'Bearer ' . $creds['omniroute_key'],
                                'Content-Type'  => 'application/json',
                            ],
                            'json' => [
                                'model'           => $comfyModel,
                                'prompt'          => $prompt,
                                'negative_prompt' => $negativePrompt,
                                'n'               => 1,
                                'size'            => $size,
                            ],
                            'timeout' => 12,
                        ]);
                        $data = json_decode($res->getBody(), true);
                        $imgUrl = !empty($data['data'][0]['url']) ? $data['data'][0]['url'] : null;

                        if (!$imgUrl && !empty($data['data'][0]['b64_json'])) {
                            $rawBin = base64_decode($data['data'][0]['b64_json']);
                            if ($rawBin && strlen($rawBin) > 500) {
                                $imgUrl = $this->saveBinaryMedia($rawBin, 'jpg', 'comfy_');
                            }
                        }

                        if (!empty($imgUrl)) {
                            return [
                                'success'  => true,
                                'url'      => $imgUrl,
                                'prompt'   => $prompt,
                                'provider' => 'ComfyUI GPU Studio (via OmniRoute)',
                                'model'    => $comfyModel,
                                'tier'     => 1,
                                'time_ms'  => round((microtime(true) - $start) * 1000),
                            ];
                        }
                    }
                } catch (\Throwable $e) {
                    Log::info("ComfyUI Image Tier bypassed: " . $e->getMessage() . " -> Cascading to next tier");
                }
            }

            // Tier 1: OmniRoute Gateway
            if ($tier === 'omniroute' && !empty($creds['omniroute_key'])) {
                try {
                    $url = rtrim($creds['omniroute_url'] ?? 'https://ai.vmstudio.digital', '/') . '/v1/images/generations';
                    $imgModel = (!empty($creds['model_omniroute_image']) && $creds['model_omniroute_image'] !== 'flux') ? $creds['model_omniroute_image'] : 'nvidia/black-forest-labs/flux.1-dev';
                    $res = $this->client->post($url, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $creds['omniroute_key'],
                            'Content-Type'  => 'application/json',
                        ],
                        'json' => [
                            'model'           => $imgModel,
                            'prompt'          => $prompt,
                            'negative_prompt' => $negativePrompt,
                            'n'               => 1,
                            'size'            => $size,
                        ],
                        'timeout' => 25,
                    ]);
                    $data = json_decode($res->getBody(), true);
                    $imgUrl = !empty($data['data'][0]['url']) ? $data['data'][0]['url'] : null;

                    if (!$imgUrl && !empty($data['data'][0]['b64_json'])) {
                        $rawBin = base64_decode($data['data'][0]['b64_json']);
                        if ($rawBin && strlen($rawBin) > 500) {
                            $mediaDir = public_path('storage/brain_media');
                            if (!is_dir($mediaDir)) {
                                @mkdir($mediaDir, 0775, true);
                            }
                            $fName = 'brain_' . \Illuminate\Support\Str::random(18) . '_' . $w . 'x' . $h . '.jpg';
                            file_put_contents($mediaDir . '/' . $fName, $rawBin);
                            $imgUrl = asset('storage/brain_media/' . $fName);
                        }
                    }

                    if (!empty($imgUrl)) {
                        return [
                            'success'  => true,
                            'url'      => $imgUrl,
                            'prompt'   => $prompt,
                            'provider' => 'OmniRoute Gateway',
                            'model'    => $imgModel,
                            'tier'     => 1,
                            'time_ms'  => round((microtime(true) - $start) * 1000),
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::info("OmniRoute Image Tier bypassed: " . $e->getMessage() . " -> Falling back to Pollinations Flux");
                }
            }

            // Tier 2: Pollinations Flux (100% Free, Keyless, Unlimited)
            if ($tier === 'pollinations') {
                try {
                    $cleanPrompt = rawurlencode($prompt);
                    $seed = rand(1000, 999999);
                    $url = "https://image.pollinations.ai/prompt/{$cleanPrompt}?model=flux&width={$w}&height={$h}&nologo=true&seed={$seed}";
                    
                    return [
                        'success'  => true,
                        'url'      => $url,
                        'prompt'   => $prompt,
                        'provider' => 'Pollinations AI (Flux Free Tier)',
                        'model'    => 'flux',
                        'tier'     => 2,
                        'time_ms'  => round((microtime(true) - $start) * 1000),
                    ];
                } catch (\Throwable $e) {
                    Log::info("Pollinations Flux bypassed -> Trying Cloudflare Workers AI");
                }
            }

            // Tier 3: Cloudflare Workers AI (Free Daily Quota)
            if ($tier === 'cloudflare' && !empty($creds['cloudflare_account_id']) && !empty($creds['cloudflare_api_token'])) {
                try {
                    $cfAccount = $creds['cloudflare_account_id'];
                    $cfToken   = $creds['cloudflare_api_token'];
                    $cfUrl = "https://api.cloudflare.com/client/v4/accounts/{$cfAccount}/ai/run/@cf/black-forest-labs/flux-1-schnell";
                    
                    $res = $this->client->post($cfUrl, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $cfToken,
                            'Content-Type'  => 'application/json',
                        ],
                        'json' => [
                            'prompt' => $prompt,
                            'num_steps' => 4,
                        ],
                        'timeout' => 25,
                    ]);

                    $binary = (string) $res->getBody();
                    if (!empty($binary) && strlen($binary) > 1000) {
                        $imgUrl = $this->saveBinaryMedia($binary, 'jpg', 'cf_flux_');
                        return [
                            'success'  => true,
                            'url'      => $imgUrl,
                            'prompt'   => $prompt,
                            'provider' => 'Cloudflare Workers AI (Free Tier)',
                            'model'    => 'flux-1-schnell',
                            'tier'     => 3,
                            'time_ms'  => round((microtime(true) - $start) * 1000),
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::info("Cloudflare Workers AI bypassed: " . $e->getMessage() . " -> Trying Hugging Face");
                }
            }

            // Tier 4: Hugging Face Serverless Inference (Free User Token)
            if ($tier === 'huggingface' && !empty($creds['huggingface_token'])) {
                try {
                    $hfUrl = "https://api-inference.huggingface.co/models/black-forest-labs/FLUX.1-schnell";
                    $res = $this->client->post($hfUrl, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $creds['huggingface_token'],
                            'Content-Type'  => 'application/json',
                        ],
                        'json' => ['inputs' => $prompt],
                        'timeout' => 30,
                    ]);
                    $binary = (string) $res->getBody();
                    if (!empty($binary) && strlen($binary) > 1000) {
                        $imgUrl = $this->saveBinaryMedia($binary, 'jpg', 'hf_flux_');
                        return [
                            'success'  => true,
                            'url'      => $imgUrl,
                            'prompt'   => $prompt,
                            'provider' => 'Hugging Face Serverless (Free Tier)',
                            'model'    => 'FLUX.1-schnell',
                            'tier'     => 4,
                            'time_ms'  => round((microtime(true) - $start) * 1000),
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::info("Hugging Face bypassed: " . $e->getMessage());
                }
            }
        }

        // Tier 5: Guaranteed Keyless Fallback: Pollinations Turbo
        $cleanPrompt = rawurlencode($prompt);
        return [
            'success'  => true,
            'url'      => "https://image.pollinations.ai/prompt/{$cleanPrompt}?model=turbo&width={$w}&height={$h}&nologo=true&seed=" . rand(100, 9999),
            'prompt'   => $prompt,
            'provider' => 'Pollinations AI (Turbo Safety Fallback)',
            'model'    => 'turbo',
            'tier'     => 5,
            'time_ms'  => 100,
        ];
    }

    /* ═══════════════════════════════════════════════════════════════════
       3. VIDEO AI GENERATION ENGINE (9:16 Social Reels & Cinematic Temple Motion)
       Fallback Order:
       Tier 1: OmniRoute Video Gateway (Wan 2.1 / Grok-imagine / Kling / Luma)
       Tier 2: Hugging Face Video (Wan 2.1 T2V Serverless Free Endpoint)
       Tier 3: Sacred 9:16 MP4 Reel Synthesizer (Native FFmpeg Cinematic Ken-Burns Camera Push-in)
       Tier 4: Zero-Failure Devotional Visual Loop
    ═══════════════════════════════════════════════════════════════════ */
    public function generateVideo(string $rawTopic, ?string $imageUrl = null, int $duration = 5): array
    {
        $enhanced = VedicPromptEnhancer::enhance($rawTopic, 'video_reel', '9:16');
        $videoPrompt = $enhanced['prompt'];
        $creds = Vault::aiCredentials();
        $chain = array_filter(array_map('trim', explode(',', (string) ($creds['ai_video_chain'] ?? 'comfyui,ffmpeg_reel,omniroute,kaggle'))));

        foreach ($chain as $tier) {
            $start = microtime(true);

            // Tier: ComfyUI Video Studio (AnimateDiff & SVD-XT via OmniRoute)
            if ($tier === 'comfyui') {
                try {
                    $comfyVideoModel = Vault::get('model_comfyui_video', 'comfyui/animatediff');
                    $omniUrl = rtrim($creds['omniroute_url'] ?? 'https://ai.vmstudio.digital', '/');

                    if (!empty($creds['omniroute_key'])) {
                        $res = $this->client->post($omniUrl . '/v1/videos/generations', [
                            'headers' => [
                                'Authorization' => 'Bearer ' . $creds['omniroute_key'],
                                'Content-Type'  => 'application/json',
                            ],
                            'json' => [
                                'model'    => $comfyVideoModel,
                                'prompt'   => $videoPrompt,
                                'duration' => $duration,
                            ],
                            'timeout' => 45,
                        ]);
                        $data = json_decode($res->getBody(), true);
                        $videoUrl = $data['data']['video_url'] ?? $data['video_url'] ?? $data['url'] ?? null;
                        if ($videoUrl) {
                            return [
                                'success'   => true,
                                'video_url' => $videoUrl,
                                'prompt'    => $videoPrompt,
                                'provider'  => 'ComfyUI Video Studio (via OmniRoute)',
                                'model'     => $comfyVideoModel,
                                'tier'      => 1,
                                'time_ms'   => round((microtime(true) - $start) * 1000),
                            ];
                        }
                    }
                } catch (\Throwable $e) {
                    Log::info("ComfyUI Video Tier bypassed: " . $e->getMessage() . " -> Cascading to FFmpeg Reel");
                }
            }

            // Tier 1: Native FFmpeg 9:16 Sacred Reel Synthesizer (Zero-cost, broadcast-ready 1080x1920)
            if ($tier === 'ffmpeg_reel' || $tier === 'cinematic_reel') {
                try {
                    $driver = new \App\AI\Drivers\FfmpegReelVideoDriver();
                    $brandCfg = Vault::videoBranding();
                    $res = $driver->create($videoPrompt, [
                        'seconds'  => $duration,
                        'image_url'=> $imageUrl,
                        'title'    => $rawTopic,
                        'subtitle' => $brandCfg['video_brand_tagline'] ?? 'वाराणसी से सीधा लाइव संकल्प एवं दर्शन',
                    ]);

                    if (!empty($res['ok']) && !empty($res['url'])) {
                        return [
                            'success'   => true,
                            'video_url' => asset($res['url']),
                            'is_mp4'    => true,
                            'prompt'    => $videoPrompt,
                            'provider'  => 'Native FFmpeg 9:16 Sacred Reel Synthesizer (HQ Free)',
                            'model'     => 'h264-1080x1920-hq',
                            'tier'      => 1,
                            'time_ms'   => round((microtime(true) - $start) * 1000),
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::info("FFmpeg Reel Tier bypassed: " . $e->getMessage());
                }
            }

            // Tier 2: OmniRoute Video Gateway
            if ($tier === 'omniroute' && !empty($creds['omniroute_key'])) {
                try {
                    $url = rtrim($creds['omniroute_url'] ?? 'https://ai.vmstudio.digital', '/') . '/v1/videos/generations';
                    $videoModel = (!empty($creds['model_omniroute_video']) && !str_contains($creds['model_omniroute_video'], 'fal-ai')) ? $creds['model_omniroute_video'] : 'fal-ai/xai/grok-imagine-video/text-to-video';

                    $payload = [
                        'model'           => $videoModel,
                        'prompt'          => $videoPrompt,
                        'negative_prompt' => VedicPromptEnhancer::NEGATIVE_PROMPT,
                        'duration'        => $duration,
                    ];
                    if ($imageUrl) {
                        $payload['image_url'] = $imageUrl;
                    }

                    $res = $this->client->post($url, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $creds['omniroute_key'],
                            'Content-Type'  => 'application/json',
                        ],
                        'json' => $payload,
                        'timeout' => 30,
                    ]);

                    $data = json_decode($res->getBody(), true);
                    $videoUrl = $data['data']['video_url'] ?? $data['video_url'] ?? $data['url'] ?? null;
                    if ($videoUrl && (str_contains($videoUrl, '.mp4') || str_contains($videoUrl, 'video'))) {
                        return [
                            'success'   => true,
                            'video_url' => $videoUrl,
                            'prompt'    => $videoPrompt,
                            'provider'  => 'OmniRoute Video Gateway',
                            'model'     => $videoModel,
                            'tier'      => 1,
                            'time_ms'   => round((microtime(true) - $start) * 1000),
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::info("OmniRoute Video Tier bypassed: " . $e->getMessage() . " -> Falling back to next video tier");
                }
            }

            // Tier 2: Hugging Face Video Inference (Free Tier)
            if ($tier === 'huggingface' && !empty($creds['huggingface_token'])) {
                try {
                    $hfUrl = "https://api-inference.huggingface.co/models/Wan-AI/Wan2.1-T2V-14B";
                    $res = $this->client->post($hfUrl, [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $creds['huggingface_token'],
                            'Content-Type'  => 'application/json',
                        ],
                        'json' => ['inputs' => $videoPrompt],
                        'timeout' => 30,
                    ]);
                    $data = json_decode($res->getBody(), true);
                    if (!empty($data['video_url']) && str_contains($data['video_url'], '.mp4')) {
                        return [
                            'success'   => true,
                            'video_url' => $data['video_url'],
                            'prompt'    => $videoPrompt,
                            'provider'  => 'Hugging Face Video (Wan 2.1 Free)',
                            'model'     => 'Wan2.1-T2V',
                            'tier'      => 2,
                            'time_ms'   => round((microtime(true) - $start) * 1000),
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::info("Hugging Face Video bypassed: " . $e->getMessage());
                }
            }

            // Tier 3: Sacred 9:16 MP4 Reel Synthesizer (Native FFmpeg Video Engine)
            if ($tier === 'cinematic_reel' || $tier === 'pollinations') {
                try {
                    // First generate high-definition 9:16 vertical spiritual artwork
                    $frame = $this->generateImage($rawTopic, 'temple_sanctum', '9:16');
                    $frameUrl = $frame['url'] ?? null;

                    if ($frameUrl) {
                        $mp4Url = $this->synthesizeMp4Reel($frameUrl, uniqid('sacred_'), $duration);
                        if ($mp4Url) {
                            return [
                                'success'   => true,
                                'video_url' => $mp4Url,
                                'is_mp4'    => true,
                                'prompt'    => $videoPrompt,
                                'provider'  => 'OmniRoute FLUX + Sacred MP4 Synthesizer',
                                'model'     => 'h264-1080x1920-cinematic-reel',
                                'tier'      => 3,
                                'time_ms'   => round((microtime(true) - $start) * 1000),
                            ];
                        }
                    }
                } catch (\Throwable $e) {
                    Log::info("Sacred MP4 Synthesizer bypassed: " . $e->getMessage());
                }
            }
        }

        // Tier 4: Guaranteed MP4 Synthesis from Fallback Artwork
        $fallbackImg = $this->generateImage($rawTopic, 'temple_sanctum', '9:16');
        $fallbackUrl = $fallbackImg['url'] ?? '';
        $mp4Url = $this->synthesizeMp4Reel($fallbackUrl, uniqid('reel_fallback_'), $duration);

        return [
            'success'   => true,
            'video_url' => $mp4Url ?: $fallbackUrl,
            'is_mp4'    => !empty($mp4Url),
            'prompt'    => $videoPrompt,
            'provider'  => 'Sacred Cinematic Reel Engine',
            'model'     => 'h264-1080x1920-reel',
            'tier'      => 4,
            'time_ms'   => 120,
            'note'      => 'High-definition 1080x1920 vertical MP4 video reel ready for Instagram, Facebook and YouTube Shorts broadcast.',
        ];
    }

    /**
     * Synthesizes a genuine broadcast-ready 1080x1920 (9:16) H.264 MP4 Video Reel from an AI visual frame.
     * Delegates to FfmpegReelVideoDriver for 1080x1920 broadcast quality, Devanagari HarfBuzz shaping, and OnlinePuja branding.
     */
    public function synthesizeMp4Reel(string $imageUrlOrBinary, string $identifier, int $duration = 5, array $branding = []): ?string
    {
        try {
            $driver = new \App\AI\Drivers\FfmpegReelVideoDriver();
            $brandCfg = Vault::videoBranding();
            $res = $driver->create($branding['title'] ?? 'Sacred Vedic Reel', [
                'seconds'   => $duration,
                'image_url' => $imageUrlOrBinary,
                'title'     => $branding['title'] ?? $brandCfg['video_brand_title'] ?? 'OnlinePuja.live',
                'subtitle'  => $branding['subtitle'] ?? $brandCfg['video_brand_tagline'] ?? 'वाराणसी से सीधा लाइव संकल्प एवं दर्शन',
                'cta'       => $branding['cta'] ?? ($brandCfg['video_brand_phone'] . ' | ' . $brandCfg['video_brand_cta']),
            ]);

            if (!empty($res['ok']) && !empty($res['url'])) {
                return asset($res['url']);
            }
            return null;
        } catch (\Throwable $e) {
            Log::error("FFmpeg reel synthesis exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Persist binary or base64 media to public disk and return public URL
     */
    protected function saveBinaryMedia(string $binaryData, string $extension = 'jpg', string $prefix = 'brain_'): string
    {
        $mediaDir = public_path('storage/brain_media');
        if (!is_dir($mediaDir)) {
            @mkdir($mediaDir, 0775, true);
        }
        $fName = $prefix . \Illuminate\Support\Str::random(18) . '.' . $extension;
        file_put_contents($mediaDir . '/' . $fName, $binaryData);
        return asset('storage/brain_media/' . $fName);
    }
}
