<?php

namespace App\Http\Controllers;

use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AiHelpController extends Controller
{
    public function ask(Request $request, AiService $aiService)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'module' => 'nullable|string|max:100',
        ]);

        $message = trim($request->message);
        $module = trim($request->module ?? 'general');

        $prompt = "
You are an AI Help Assistant for a farm control system.

Module: {$module}

User question:
{$message}

Please answer clearly and practically.
If the user writes Khmer, answer in Khmer.
";

        try {
            $answer = $aiService->ask($prompt);

            Log::info('AI Help answer result', [
                'module' => $module,
                'has_answer' => !empty($answer),
                'answer_preview' => $answer ? mb_substr($answer, 0, 100) : null,
            ]);

            if (!$answer) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI returned empty response. Please check provider, model, API key, and Laravel log.',
                    'answer' => null,
                    'reply' => null,
                    'content' => null,
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => $answer,
                'answer' => $answer,
                'reply' => $answer,
                'content' => $answer,
            ]);
        } catch (\Throwable $e) {
            Log::error('AI Help Controller error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'AI Help error: ' . $e->getMessage(),
                'answer' => null,
                'reply' => null,
                'content' => null,
            ], 500);
        }
    }
}