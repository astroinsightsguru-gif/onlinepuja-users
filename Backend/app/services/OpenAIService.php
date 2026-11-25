<?php

namespace App\Services;

use App\Models\AdminModel\SystemFlag;
use GuzzleHttp\Client;
use Exception;
use App\Models\AiAstrologerModel\AiAstrologer;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class OpenAIService
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $api_key=SystemFlag::where('name','OpenAiKey')->first();
        $this->apiKey = $api_key->value;
        if (!$this->apiKey) {
            \Log::error('OpenAI API Key not found');
            throw new Exception('API Key not found.');
        }
    }

    public function askChatGPT($message,$astrologerId)
    {

        $assistantContent = $this->getAssistantContentBasedOnAstrologer($astrologerId); // Example function to get dynamic content based on astrologer ID
         $userId = authcheck()['id'];
        $user = User::where('id', $userId)->select(['name', 'birthDate', 'birthPlace'])->first();
        
        // Format the user data into the message
        $userInfo = "mera name {$user->name}, mera date of birth {$user->birthDate}, aur mera place of birth is {$user->birthPlace}.";
        $finalMessage = "{$userInfo} {$message}";

        try {
            $response = $this->client->post('https://api.openai.com/v1/chat/completions', [

                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-4',
                    'messages' => [
                        ['role' => 'system', 'content' => $assistantContent],
                        ['role' => 'user', 'content' => $finalMessage],
                    ],
                    'max_tokens' => 200,
                    'temperature' => 0.5,
                    'top_p' => 0.7,
                    'frequency_penalty'=>0,
                    'presence_penalty'=>0,
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            $content = $data['choices'][0]['message']['content'];
            $content = $this->stopAtLastPeriod($content);

            return $content;
        } catch (RequestException $e) {
            if ($e->getCode() == 429) {
                $attempts++;
                sleep(2);
            } else {
                \Log::error('Request error: ' . $e->getMessage());
                return 'Error communicating with OpenAI API.';
            }
        } catch (Exception $e) {
            \Log::error('General error: ' . $e->getMessage());
            return $e->getMessage();
        }

    }

    public function askChatGPTMaster($message)
    {
        $assistantContent = AiAstrologer::where('type','master')->value('system_intruction');
        $userId = authcheck()['id'];
        $user = User::where('id', $userId)->select(['name', 'birthDate', 'birthPlace'])->first();
        
        // $userInfo = "My name is {$user->name}, my date of birth is {$user->birthDate}, and my place of birth is {$user->birthPlace}.";
        $userInfo = "mera name {$user->name}, mera date of birth {$user->birthDate}, aur mera place of birth is {$user->birthPlace}.";

        $finalMessage = "{$userInfo} {$message}";

        try {
            $response = $this->client->post('https://api.openai.com/v1/chat/completions', [

                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-4',
                    'messages' => [
                        ['role' => 'system', 'content' => $assistantContent],
                        ['role' => 'user', 'content' => $finalMessage],
                    ],
                    'max_tokens' => 350,
                    'temperature' => 0.5,
                    'top_p' => 0.7,
                    'frequency_penalty'=>0,
                    'presence_penalty'=>0,
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            $content = $data['choices'][0]['message']['content'];
            $content = $this->stopAtLastPeriod($content);

            return $content;
        } catch (RequestException $e) {
            if ($e->getCode() == 429) {
                $attempts++;
                sleep(2);
            } else {
                \Log::error('Request error: ' . $e->getMessage());
                return 'Error communicating with OpenAI API.';
            }
        } catch (Exception $e) {
            \Log::error('General error: ' . $e->getMessage());
            return $e->getMessage();
        }

    }

    private function stopAtLastPeriod($content)
    {
        // Trim any extra spaces from the response
        $content = trim($content);

        // Find the position of the last period (.)
        $lastPeriodPos = strrpos($content, '.');

        // If a period is found, truncate everything after the last period
        if ($lastPeriodPos !== false) {
            // Cut the response to the position of the last period
            $content = substr($content, 0, $lastPeriodPos + 1);
        }

        // Return the trimmed content
        return $content;
    }
    private function getAssistantContentBasedOnAstrologer($astrologerId)
    {
        // 1. Check if it's an AiAstrologer bot
        $astrologer = AiAstrologer::find($astrologerId);
        if ($astrologer && !empty($astrologer->system_intruction)) {
            return $astrologer->system_intruction;
        }

        // 2. Check if it's a real Astrologer's AI Twin
        $humanAstro = \Illuminate\Support\Facades\DB::table('astrologers')->where('id', $astrologerId)->first();
        if ($humanAstro) {
            $astroName = $humanAstro->name ?? 'आचार्य';
            $tone = $humanAstro->ai_twin_tone ?? 'empathetic';
            $prompt = $humanAstro->ai_twin_prompt ?? '';
            $skills = $humanAstro->allSkills ?? $humanAstro->primarySkill ?? 'Vedic Astrology, Kundli, Prashna';
            $exp = $humanAstro->experienceInYears ?? '10+';

            return "You are the official Vedic AI Digital Twin of Acharya {$astroName} on OnlinePuja.live.
You represent Acharya {$astroName} ({$exp} years of Vedic experience, specialties: {$skills}).
Persona & Tone: {$tone}. Speak with divine grace, compassionate Vedic wisdom, and deep Jyotish authority in pure Hindi or English matching the devotee.
Acharya Ji's Guiding Philosophy & Custom Training Rules:
{$prompt}
Rules for Consultation:
1. Always analyze devotee's birth chart (Kundli), planetary transits (Gochar), and Dasha periods with reverence.
2. Recommend authentic sanctified remedies, Vedic mantras, and certified OnlinePuja rituals (e.g. Mahamrityunjay Jaap, Kaal Bhairav Anushthan, Rudrabhishek) strictly aligned with Acharya Ji's philosophy.
3. Be respectful, encouraging, and provide clear spiritual hope and actionable Vedic remedies.";
        }

        return "You are an experienced Vedic astrologer on OnlinePuja.live. Your role is to provide insightful and personalized astrological readings based on users' birth details. Use your knowledge of Vedic astrology to interpret planetary positions, aspects, and transits to help users understand their past, present, and future. Be empathetic and supportive in your responses.";
    }



}
