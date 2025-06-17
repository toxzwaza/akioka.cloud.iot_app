<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatGpt
{
    /**
     * ChatGPT APIにリクエストを送信してレスポンスを取得
     *
     * @param string $message
     * @return array
     */
    public static function getResponse($message)
    {
        try {
            $apiKey = config('services.openai.api_key');
            
            if (!$apiKey) {
                Log::error('OpenAI API key not configured');
                return [
                    'choices' => [
                        [
                            'message' => [
                                'content' => 'APIキーが設定されていません。管理者にお問い合わせください。'
                            ]
                        ]
                    ]
                ];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'あなたは稟議書作成の専門家です。ユーザーが入力した内容について、より良い稟議書を作成するためのアドバイスを提供してください。'
                    ],
                    [
                        'role' => 'user',
                        'content' => $message
                    ]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('OpenAI API error: ' . $response->body());
                return [
                    'choices' => [
                        [
                            'message' => [
                                'content' => 'APIリクエストに失敗しました。しばらく時間をおいてから再度お試しください。'
                            ]
                        ]
                    ]
                ];
            }
        } catch (\Exception $e) {
            Log::error('ChatGPT API exception: ' . $e->getMessage());
            return [
                'choices' => [
                    [
                        'message' => [
                            'content' => 'エラーが発生しました。しばらく時間をおいてから再度お試しください。'
                        ]
                    ]
                ]
            ];
        }
    }
} 