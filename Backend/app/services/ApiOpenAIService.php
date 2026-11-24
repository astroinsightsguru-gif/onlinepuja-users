<?php

namespace App\Services;

use App\Models\AdminModel\SystemFlag;
use App\Models\AiAstrologerModel\AiAstrologer;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Exception;

class ApiOpenAIService
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $api_key = SystemFlag::where('name', 'OpenAiKey')->first();
        $this->apiKey = $api_key->value;
        if (!$this->apiKey) {
            \Log::error('OpenAI API Key not found');
            throw new Exception('API Key not found.');
        }
    }

    public function askChatGPT($message, $astrologerId)
    {
        $assistantContent = $this->getAssistantContentBasedOnAstrologer($astrologerId);  // Example function to get dynamic content based on astrologer ID
        $userId = Auth::guard('api')->user()->id;
        $user = User::where('id', $userId)->select(['name', 'birthDate', 'birthPlace'])->first();

        // Format the user data into the message
        $userInfo = "mera name {$user->name}, mera date of birth {$user->birthDate}, aur mera place of birth is {$user->birthPlace}.";
        $finalMessage = "{$userInfo} {$message}";

        try {
            $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
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
                    'frequency_penalty' => 0,
                    'presence_penalty' => 0,
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
        // Rule Instructions

        //     You are an expert astrologer.

        // Rules:
        // - Provide astrological insights strictly based on traditional astrological principles.
        // - Give guidance or predictions ONLY for career, love, health, and finance.
        // - Answer ONLY what is relevant to the user’s question.
        // - Keep the response short, clear, and focused.
        // - Do NOT add extra analysis, remedies, or predictions unless explicitly asked.
        // - If a question goes beyond astrological knowledge, respond exactly with:
        //   "This is beyond my limit as an astrologer."
        // - Respond in Hinglish (simple Hindi + English).

        $assistantContent = AiAstrologer::where('type', 'master')->value('system_intruction');

        $user = Auth::guard('api')->user();
        $userInfo = "Name: {$user->name}, DOB: {$user->birthDate}, Place: {$user->birthPlace}";

        try {
            $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-4.1',
                    'messages' => [
                        ['role' => 'system', 'content' => $assistantContent],
                        ['role' => 'user', 'content' => "User details (reference only): {$userInfo}"],
                        ['role' => 'user', 'content' => "Question: {$message}"],
                    ],
                    'temperature' => 0.2,
                    'top_p' => 0.5,
                    'max_tokens' => 120,
                ],
            ]);

            return trim(
                json_decode($response->getBody(), true)['choices'][0]['message']['content']
            );
        } catch (\Exception $e) {
            \Log::error('AI Error: ' . $e->getMessage());
            return 'AI service temporarily unavailable.';
        }
    }

    private function stopAtLastPeriod($content)
    {
        // Trim any extra spaces from the content
        $content = trim($content);

        // Split the content into lines
        $lines = explode("\n", $content);

        // Remove the last line if it exists
        if (!empty($lines)) {
            array_pop($lines);
        }

        // Join the remaining lines back into a single string
        $content = implode("\n", $lines);

        // Return the modified content
        return $content;
    }

    private function getAssistantContentBasedOnAstrologer($astrologerId)
    {
        $astrologer = AiAstrologer::find($astrologerId);

        if ($astrologer) {
            return $astrologer->system_intruction;
        }
        return "You are an experienced astrologer. Your role is to provide insightful and personalized
          astrological readings based on users' birth details. Use your knowledge of astrology to interpret planetary
           positions, aspects, and transits to help users understand their past, present, and future. Be empathetic
            and supportive in your responses.";
    }
}
