<?php

namespace App\Services;

use App\Models\AiSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    public function ask(string $prompt): ?string
    {
        $setting = AiSetting::active();

        if (!$setting || !$setting->is_enabled || empty($setting->api_key)) {
            return null;
        }

        $provider = strtolower(trim($setting->provider));
        $model = trim($setting->model ?? '');
        $apiKey = trim($setting->api_key);

        try {
            return match ($provider) {
                'openai' => $this->askOpenAI($apiKey, $model, $prompt),
                'anthropic' => $this->askAnthropic($apiKey, $model, $prompt),
                'gemini' => $this->askGemini($apiKey, $model, $prompt),
                'groq' => $this->askGroq($apiKey, $model, $prompt),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::error('AI request failed', [
                'provider' => $provider,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function askOpenAI(string $apiKey, string $model, string $prompt): ?string
    {
        $response = Http::withOptions([
                'curl' => [
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                ],
            ])
            ->connectTimeout(30)
            ->timeout(60)
            ->withToken($apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model ?: 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.7,
                'max_tokens' => 1000,
            ]);

        if (!$response->successful()) {
            Log::error('OpenAI error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json('choices.0.message.content');
    }

    private function askGroq(string $apiKey, string $model, string $prompt): ?string
    {
        $response = Http::withOptions([
                'curl' => [
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                ],
            ])
            ->connectTimeout(30)
            ->timeout(60)
            ->withToken($apiKey)
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => $model ?: 'llama-3.1-8b-instant',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.7,
                'max_tokens' => 1000,
            ]);

        if (!$response->successful()) {
            Log::error('Groq error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json('choices.0.message.content');
    }

    private function askGemini(string $apiKey, string $model, string $prompt): ?string
    {
        $model = $model ?: 'gemini-2.5-flash';

        $response = Http::connectTimeout(30)
            ->timeout(60)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt,
                            ],
                        ],
                    ],
                ],
            ]);

        if (!$response->successful()) {
            Log::error('Gemini error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json('candidates.0.content.parts.0.text');
    }

    private function askAnthropic(string $apiKey, string $model, string $prompt): ?string
    {
        $response = Http::connectTimeout(30)
            ->timeout(60)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $model ?: 'claude-3-5-haiku-latest',
                'max_tokens' => 1000,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

        if (!$response->successful()) {
            Log::error('Anthropic error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json('content.0.text');
    }
}